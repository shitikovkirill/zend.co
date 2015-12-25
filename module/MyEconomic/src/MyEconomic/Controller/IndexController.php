<?php
namespace MyEconomic\Controller;

use MyEconomic\Entity\EconomicUser;
use MyEconomic\Form\EUserForm;
use Zend\Mvc\Controller\AbstractActionController;
use MyEconomic\Entity\Turnover;

class IndexController extends AbstractActionController
{
    public function indexAction(){

    }
    public function adduserAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $yearntityManager = $this->getServiceLocator()
            ->get('doctrine.entitymanager.orm_default');

        $user = $this->zfcUserAuthentication()->getIdentity();
        $economicUser = $yearntityManager->getRepository('MyEconomic\Entity\EconomicUser')->findOneBy(array('user'=>$user));
        if(empty($economicUser)){
            $economicUser = new EconomicUser();
        }


        $form = new EUserForm($economicUser);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $yearconomicUser = $form->getData();
                $yearconomicUser->setUser($user);
                $yearntityManager->persist($yearconomicUser);
                $yearntityManager->flush();
                $this->flashMessenger()->addSuccessMessage($translator->translate('E-conomic User saved'));
                //$this->redirect()->toRoute('');
            }
        }
        $form->prepare();
        return array(
            'form' => $form
        );
    }

    public function getdataAction()
    {
        //try {
        $wsdlUrl = 'https://api.e-conomic.com/secure/api1/EconomicWebservice.asmx?WSDL'; //$this->getServiceLocator()->get('url_api');

        $client = new \SoapClient($wsdlUrl, array("trace" => 1, "exceptions" => 1));

        $yearntityManager = $this->getServiceLocator()
            ->get('doctrine.entitymanager.orm_default');

        $yearUser = $yearntityManager->getRepository('MyEconomic\Entity\EconomicUser')->find(1);
        $user = $yearntityManager->getRepository('MyUser\Entity\User')->find(1);

        $client->Connect(array(
            'agreementNumber' => $yearUser->getAgreementNumber(),
            'userName' => $yearUser->getUsername(),
            'password' => $yearUser->getPassword()));

        $accYears = $client->AccountingPeriod_GetAll()->AccountingPeriod_GetAllResult->AccountingPeriodHandle;
        $accPeriodData = $client->AccountingPeriod_GetDataArray(array('entityHandles' => $accYears))->AccountingPeriod_GetDataArrayResult;

        /* Get accounting periods */
        $accountPeriods = array();
        foreach ($accPeriodData->AccountingPeriodData as $period) {
            $tmp['Year'] = $period->AccountingYearHandle->Year;
            $tmp['FromDate'] = $period->FromDate;
            $tmp['ToDate'] = $period->ToDate;
            array_push($accountPeriods, $tmp);
        };

        /* Get turnovers by periods */
        $keyFigureCodeHundlers = $client->KeyFigureCode_FindByNumber(array('number' => '1'))->KeyFigureCode_FindByNumberResult;
        $accs = $client->KeyFigureCode_GetAccounts(array('keyFigureCodeHandle' => $keyFigureCodeHundlers))->KeyFigureCode_GetAccountsResult;

        $tmp = array();
        $turnover2011 = array();
        $turnover2012 = array();
        $turnover2013 = array();
        $turnover2014 = array();
        $turnover2015 = array();
        $turnover2016 = array();
        foreach ($accountPeriods as $period) {
            if ($period['Year'] == '2011') {
                //$tmp['sub'] = substr($period['FromDate'], 5, 2);
                $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                array_push($turnover2011, $tmp);
            }
            if ($period['Year'] == '2012') {
                //$tmp['sub'] = substr($period['FromDate'], 5, 2);
                $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                array_push($turnover2012, $tmp);
            }
            if ($period['Year'] == '2013') {
                //$tmp['sub'] = substr($period['FromDate'], 5, 2);
                $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                array_push($turnover2013, $tmp);
            }
            if ($period['Year'] == '2014') {
                //$tmp['sub'] = substr($period['FromDate'], 5, 2);
                $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                array_push($turnover2014, $tmp);
            }
            if ($period['Year'] == '2015') {
                //$tmp['sub'] = substr($period['FromDate'], 5, 2);
                $tmp= abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                array_push($turnover2015, $tmp);
            }
            if ($period['Year'] == '2016') {
                //$tmp['sub'] = substr($period['FromDate'], 5, 2);
                $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                array_push($turnover2016, $tmp);
            }
        }


        $turnover = array(
            '2011' => $turnover2011,
            '2012' => $turnover2012,
            '2013' => $turnover2013,
            '2014' => $turnover2014,
            '2015' => $turnover2015,
            '2016' => $turnover2016
        );

        foreach ($turnover as $key => $value) {

            $turn = new Turnover();
            $turn->setUser($user);
            $turn->setYear($key);

            $turn->setTurnover(json_encode($value));
            // var_dump($turn);
            $yearntityManager->persist($turn);
            $yearntityManager->flush();
        }
        //}
    }
    public function turnoverAction(){
        $year = new \DateTime();
        $year = $year -> format('Y');
        $year =  $this->getRequest()->getPost('year', $year);
        $yearntityManager = $this->getServiceLocator()
            ->get('doctrine.entitymanager.orm_default');
        $user = $yearntityManager->getRepository('MyUser\Entity\User')->find(1);

        $turnoverAll = $yearntityManager->getRepository('MyEconomic\Entity\Turnover')->findBy(array('user'=>$user));
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
