<?php
namespace MyUser\Controller;

use MyUser\Form\EUserForm;
use Zend\Mvc\Controller\AbstractActionController;
use MyUser\Entity\EconomicUser;
use MyUser\Entity\Turnover;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $entityManager = $this->getServiceLocator()
            ->get('doctrine.entitymanager.orm_default');
//        $eUser = new EconomicUser();
        $user = $entityManager->getRepository('MyUser\Entity\User')->find(1);
        $eUser = $entityManager->getRepository('MyUser\Entity\EconomicUser')->find(1);
//        $eUser ->setUser($user);
//        $entityManager->persist($eUser);
//        $entityManager->flush();


        $translator = $this->getServiceLocator()->get('translator');
        $form = new EUserForm($eUser);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $economicUser = $form->getData();
                $economicUser->setUser($user);
                $entityManager->persist($economicUser);
                $entityManager->flush();
                $this->flashMessenger()->addSuccessMessage($translator->translate('E-conomic User saved'));
                //$this->redirect()->toRoute('');
            }
        }
        $form->prepare();
        return array(
            'form' => $form
        );
    }

    public function xxxAction()
    {
        //try {
            $wsdlUrl = 'https://api.e-conomic.com/secure/api1/EconomicWebservice.asmx?WSDL';

            $client = new \SoapClient($wsdlUrl, array("trace" => 1, "exceptions" => 1));

            $entityManager = $this->getServiceLocator()
                ->get('doctrine.entitymanager.orm_default');

            $eUser = $entityManager->getRepository('MyUser\Entity\EconomicUser')->find(1);
            $user = $entityManager->getRepository('MyUser\Entity\User')->find(1);

            $client->Connect(array(
                'agreementNumber' => $eUser->getAgreementNumber(),
                'userName' => $eUser->getUsername(),
                'password' => $eUser->getPassword()));

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
                    $tmp['sub'] = substr($period['FromDate'], 5, 2);
                    $tmp['turnover'] = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($turnover2011, $tmp);
                }
                if ($period['Year'] == '2012') {
                    $tmp['sub'] = substr($period['FromDate'], 5, 2);
                    $tmp['turnover'] = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($turnover2012, $tmp);
                }
                if ($period['Year'] == '2013') {
                    $tmp['sub'] = substr($period['FromDate'], 5, 2);
                    $tmp['turnover'] = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($turnover2013, $tmp);
                }
                if ($period['Year'] == '2014') {
                    $tmp['sub'] = substr($period['FromDate'], 5, 2);
                    $tmp['turnover'] = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($turnover2014, $tmp);
                }
                if ($period['Year'] == '2015') {
                    $tmp['sub'] = substr($period['FromDate'], 5, 2);
                    $tmp['turnover'] = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($turnover2015, $tmp);
                }
                if ($period['Year'] == '2016') {
                    $tmp['sub'] = substr($period['FromDate'], 5, 2);
                    $tmp['turnover'] = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
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
                foreach ($value as $year) {
                    if ($year['sub'] == '01') $turn->setJan($year['turnover']);
                    if ($year['sub'] == '02') $turn->setFeb($year['turnover']);
                    if ($year['sub'] == '03') $turn->setMar($year['turnover']);
                    if ($year['sub'] == '04') $turn->setApr($year['turnover']);
                    if ($year['sub'] == '05') $turn->setMay($year['turnover']);
                    if ($year['sub'] == '06') $turn->setJun($year['turnover']);
                    if ($year['sub'] == '07') $turn->setJul($year['turnover']);
                    if ($year['sub'] == '08') $turn->setAug($year['turnover']);
                    if ($year['sub'] == '09') $turn->setSep($year['turnover']);
                    if ($year['sub'] == '10') $turn->setOct($year['turnover']);
                    if ($year['sub'] == '11') $turn->setNov($year['turnover']);
                    if ($year['sub'] == '12') $turn->setDec($year['turnover']);
                }
                $entityManager->persist($turn);
                $entityManager->flush();
            }
        //}
    }
}
