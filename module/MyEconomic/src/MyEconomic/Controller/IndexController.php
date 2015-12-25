<?php
namespace MyEconomic\Controller;

use MyEconomic\Entity\EconomicUser;
use MyEconomic\Form\EUserForm;
use Zend\Mvc\Controller\AbstractActionController;
use MyEconomic\Entity\Turnover;

class IndexController extends AbstractActionController
{
    public function indexAction(){
        if ($this->zfcUserAuthentication()->hasIdentity()) {

        } else {
           return $this->redirect()->toRoute('zfcuser');
        }
    }

    /********************************
     * Add E-conomic credentials for
     *      current user
     *******************************/
    public function adduserAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $user = $this->zfcUserAuthentication()->getIdentity();
        $entityManager = $this->getServiceLocator()
            ->get('doctrine.entitymanager.orm_default');
        $economicUser = $entityManager->getRepository('MyEconomic\Entity\EconomicUser')->findOneBy(array('user'=>$user));

        if(empty($economicUser)){
            $economicUser = new EconomicUser();
        }

        $form = new EUserForm($economicUser);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $economicUser = $form->getData();
                $economicUser->setUser($user);
                $entityManager->persist($economicUser);
                $entityManager->flush();
                $this->flashMessenger()->addSuccessMessage($translator->translate('E-conomic User saved'));
            }
        }
        $form->prepare();
        return array(
            'form' => $form
        );
    }

    /********************************
     * Load User Data from E-conomic
     *      to local database
     *******************************/
    public function loaddataAction()
    {
        //try {
        $wsdlUrl = 'https://api.e-conomic.com/secure/api1/EconomicWebservice.asmx?WSDL';
                
        $client = new \SoapClient($wsdlUrl, array("trace" => 1, "exceptions" => 1));

        $translator = $this->getServiceLocator()->get('translator');

        /* Get current local user */
        $user = $this->zfcUserAuthentication()->getIdentity();
        $entityManager = $this->getServiceLocator()
            ->get('doctrine.entitymanager.orm_default');

        /* Get E-conomic credentials for current user */
        $eUser = $entityManager->getRepository('MyEconomic\Entity\EconomicUser')->findOneBy(array('user'=>$user));

        /* Connect to E-conomic SOAP API server */
        if(empty($eUser)){
            return $this->redirect()->toRoute('myeconomic', array('action'=>'adduser'));
        }

        /* Connect to E-conomic SOAP API server */
        $client->Connect(array(
            'agreementNumber' => $eUser->getAgreementNumber(),
            'userName' => $eUser->getUsername(),
            'password' => $eUser->getPassword()));

        /* Get accounting years */
        $accYears = $client->AccountingPeriod_GetAll()->AccountingPeriod_GetAllResult->AccountingPeriodHandle;
        $accPeriodData = $client->AccountingPeriod_GetDataArray(array('entityHandles' => $accYears))->AccountingPeriod_GetDataArrayResult;
        
        /* Get accounting periods */
        $accountPeriods = array();
        $tmpPeriod = array();
        $tmp = array();
        $counter = count($accPeriodData->AccountingPeriodData);
        foreach ($accPeriodData->AccountingPeriodData as $period) {
            if (!in_array($period->AccountingYearHandle->Year, $tmp)) {
                array_push($tmp, $period->AccountingYearHandle->Year);
                if ($tmpPeriod) array_push($accountPeriods, $tmpPeriod);
                $tmpPeriod = array('Year' => $period->AccountingYearHandle->Year, 'SubPeriods' => array());
            }

            $tmpSubPeriod['FromDate'] = $period->FromDate;
            $tmpSubPeriod['ToDate'] = $period->ToDate;

            array_push($tmpPeriod['SubPeriods'], $tmpSubPeriod);

            $counter--;
            if (!$counter) {
                array_push($accountPeriods, $tmpPeriod);
            }
        };        

        /* Get turnovers */
        $keyFigureCodeHundlers = $client->KeyFigureCode_FindByNumber(array('number' => '1'))->KeyFigureCode_FindByNumberResult;
        $accs = $client->KeyFigureCode_GetAccounts(array('keyFigureCodeHandle' => $keyFigureCodeHundlers))->KeyFigureCode_GetAccountsResult;
        
        /* Get turnovers array for every month */
        foreach ($accountPeriods as $year) {
            $tmpPeriod = array();
            foreach ($year['SubPeriods'] as $period) {
                $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                array_push($tmpPeriod, $tmp);
            }
            $turnover[$year['Year']] = $tmpPeriod;
        }

        /* Save Turnovers in local database */
        foreach ($turnover as $key => $value) {
            /* check if record already exist */
            $tmp = $entityManager->getRepository('MyEconomic\Entity\Turnover')->findOneBy(array('user' => $user, 'year' => $key));
            $turn = $tmp ? $tmp : new Turnover();

            /* fill fields with data */
            $turn->setUser($user);
            $turn->setYear($key);
            $turn->setTurnover(json_encode($value));

            /* save */
            $entityManager->persist($turn);
            $entityManager->flush();
        } 

        /* Close connection */
        $client->Disconnect();

        $resultMessage = 'Your data loaded from E-Conomic to local service successfully!';

        return array( 'message' => $resultMessage );
    }

    /********************************
     * Show page with Turnover charts
     *******************************/
    public function turnoverAction(){
        $user = $this->zfcUserAuthentication()->getIdentity();
        $entityManager = $this->getServiceLocator()
            ->get('doctrine.entitymanager.orm_default');
        $eUser = $entityManager->getRepository('MyEconomic\Entity\EconomicUser')->findOneBy(array('user'=>$user));

        if(empty($eUser)){
            return $this->redirect()->toRoute('myeconomic', array('action'=>'adduser'));
        }

        $year = new \DateTime();
        $year = $year -> format('Y');
        $year =  $this->getRequest()->getPost('year', $year);

        $turnoverAll = $entityManager->getRepository('MyEconomic\Entity\Turnover')->findBy(array('user'=>$user));

        $years = array();
        $turnoverTotal = array();
        $turnoverAverage = array();
        foreach($turnoverAll as $turnover){
            array_push($years, $turnover->getYear());
            $tmp = json_decode($turnover->getTurnover());
            $total = array_sum($tmp);
            array_push($turnoverTotal, $total);
            if($turnover->getYear()==$year || $turnover->getYear()==$year-1){
                array_push($turnoverAverage, $total/12);

            };
            if($turnover->getYear()==$year){
                $turnoverThis=$tmp;
            }elseif($turnover->getYear()==$year-1){
                $turnoverLast=$tmp;
            }
        }

        $dif = array();
        foreach($turnoverThis as $key => $val){
            if($val<$turnoverLast[$key]){
                array_push($dif,$key);
            }
        }

        return array(
            "year"               => (int)$year,
            "turnoverThis"          => $turnoverThis,
            "turnoverLast"          => $turnoverLast,
            "dif"                   => $dif,
            "years"                 => $years,
            "turnoverTotal"         => $turnoverTotal,
            "turnoverAverage"       => $turnoverAverage);
    }
}
