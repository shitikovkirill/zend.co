<?php
namespace MyEconomic\Controller;

use MyEconomic\Entity\EconomicUser;
use MyEconomic\Form\EUserForm;
use Zend\Mvc\Controller\AbstractActionController;
use MyEconomic\Entity\Turnover;
use MyEconomic\Entity\VariableCosts;
use MyEconomic\Entity\OtherDirectCosts;
use MyEconomic\Entity\CompanyTax;
use MyEconomic\Entity\Depreciation;
use MyEconomic\Entity\DirectPay;
use MyEconomic\Entity\ExtraordinaryItems;
use MyEconomic\Entity\FinancialItems;
use MyEconomic\Entity\Overheads;

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
        
        if ($accs->AccountHandle) {
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
                $turnoverRecord = $tmp ? $tmp : new Turnover();

                /* fill fields with data */
                $turnoverRecord->setUser($user);
                $turnoverRecord->setYear($key);
                $turnoverRecord->setTurnover(json_encode($value));

                /* save */
                $entityManager->persist($turnoverRecord);
                $entityManager->flush();
            }
        }

        /* Get Variable Costs */
        $keyFigureCodeHundlers = $client->KeyFigureCode_FindByNumber(array('number' => '2'))->KeyFigureCode_FindByNumberResult;
        $accs = $client->KeyFigureCode_GetAccounts(array('keyFigureCodeHandle' => $keyFigureCodeHundlers))->KeyFigureCode_GetAccountsResult;
        
        if ($accs->AccountHandle) {
            /* Get variable costs array for every month */
            foreach ($accountPeriods as $year) {
                $tmpPeriod = array();
                foreach ($year['SubPeriods'] as $period) {
                    $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($tmpPeriod, $tmp);
                }
                $variableCosts[$year['Year']] = $tmpPeriod;
            }

            /* Save Variable Costs in local database */
            foreach ($variableCosts as $key => $value) {
                /* check if record already exist */
                $tmp = $entityManager->getRepository('MyEconomic\Entity\VariableCosts')->findOneBy(array('user' => $user, 'year' => $key));
                $variableCostsRecord = $tmp ? $tmp : new VariableCosts();

                /* fill fields with data */
                $variableCostsRecord->setUser($user);
                $variableCostsRecord->setYear($key);
                $variableCostsRecord->setVariableCosts(json_encode($value));

                /* save */
                $entityManager->persist($variableCostsRecord);
                $entityManager->flush();
            }
        }

        /* Get Company Tax */
        $keyFigureCodeHundlers = $client->KeyFigureCode_FindByNumber(array('number' => '9'))->KeyFigureCode_FindByNumberResult;
        $accs = $client->KeyFigureCode_GetAccounts(array('keyFigureCodeHandle' => $keyFigureCodeHundlers))->KeyFigureCode_GetAccountsResult;
        
        if ($accs->AccountHandle) {
            /* Get Company Tax array for every month */
            foreach ($accountPeriods as $year) {
                $tmpPeriod = array();
                foreach ($year['SubPeriods'] as $period) {
                    $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($tmpPeriod, $tmp);
                }
                $companyTax[$year['Year']] = $tmpPeriod;
            }

            /* Save Company Tax in local database */
            foreach ($companyTax as $key => $value) {
                /* check if record already exist */
                $tmp = $entityManager->getRepository('MyEconomic\Entity\CompanyTax')->findOneBy(array('user' => $user, 'year' => $key));
                $companyTaxRecord = $tmp ? $tmp : new CompanyTax();

                /* fill fields with data */
                $companyTaxRecord->setUser($user);
                $companyTaxRecord->setYear($key);
                $companyTaxRecord->setCompanyTax(json_encode($value));

                /* save */
                $entityManager->persist($companyTaxRecord);
                $entityManager->flush();
            }
        }

        /* Get Direct Pay */
        $keyFigureCodeHundlers = $client->KeyFigureCode_FindByNumber(array('number' => '3'))->KeyFigureCode_FindByNumberResult;
        $accs = $client->KeyFigureCode_GetAccounts(array('keyFigureCodeHandle' => $keyFigureCodeHundlers))->KeyFigureCode_GetAccountsResult;
        
          
            /* Get Direct Pay array for every month */
            foreach ($accountPeriods as $year) {
                $tmpPeriod = array();
                foreach ($year['SubPeriods'] as $period) {
                    if ($accs->AccountHandle) {
                        $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    } else {
                        $tmp = 0;
                    }
                    array_push($tmpPeriod, $tmp);
                }
                $directPay[$year['Year']] = $tmpPeriod;
            }

            /* Save Direct Pay in local database */
            foreach ($directPay as $key => $value) {
                /* check if record already exist */
                $tmp = $entityManager->getRepository('MyEconomic\Entity\DirectPay')->findOneBy(array('user' => $user, 'year' => $key));
                $directPayRecord = $tmp ? $tmp : new DirectPay();

                /* fill fields with data */
                $directPayRecord->setUser($user);
                $directPayRecord->setYear($key);
                $directPayRecord->setDirectPay(json_encode($value));

                /* save */
                $entityManager->persist($directPayRecord);
                $entityManager->flush();
            }
        

        /* Get OtherDirectCosts */
        $keyFigureCodeHundlers = $client->KeyFigureCode_FindByNumber(array('number' => '4'))->KeyFigureCode_FindByNumberResult;
        $accs = $client->KeyFigureCode_GetAccounts(array('keyFigureCodeHandle' => $keyFigureCodeHundlers))->KeyFigureCode_GetAccountsResult;
        
        
            /* Get OtherDirectCosts array for every month */
            foreach ($accountPeriods as $year) {
                $tmpPeriod = array();
                foreach ($year['SubPeriods'] as $period) {
                    if ($accs->AccountHandle) {
                        $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    } else {
                        $tmp = 0;
                    }
                    array_push($tmpPeriod, $tmp);
                }
                $otherDirectCosts[$year['Year']] = $tmpPeriod;
            }

            /* Save OtherDirectCosts in local database */
            foreach ($otherDirectCosts as $key => $value) {
                /* check if record already exist */
                $tmp = $entityManager->getRepository('MyEconomic\Entity\OtherDirectCosts')->findOneBy(array('user' => $user, 'year' => $key));
                $otherDirectCostsRecord = $tmp ? $tmp : new OtherDirectCosts();

                /* fill fields with data */
                $otherDirectCostsRecord->setUser($user);
                $otherDirectCostsRecord->setYear($key);
                $otherDirectCostsRecord->setOtherDirectCosts(json_encode($value));

                /* save */
                $entityManager->persist($otherDirectCostsRecord);
                $entityManager->flush();
            }
        

        /* Get Overheads */
        $keyFigureCodeHundlers = $client->KeyFigureCode_FindByNumber(array('number' => '5'))->KeyFigureCode_FindByNumberResult;
        $accs = $client->KeyFigureCode_GetAccounts(array('keyFigureCodeHandle' => $keyFigureCodeHundlers))->KeyFigureCode_GetAccountsResult;
        
        if ($accs->AccountHandle) {
            /* Get Overheads array for every month */
            foreach ($accountPeriods as $year) {
                $tmpPeriod = array();
                foreach ($year['SubPeriods'] as $period) {
                    $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($tmpPeriod, $tmp);
                }
                $overheads[$year['Year']] = $tmpPeriod;
            }

            /* Save Overheads in local database */
            foreach ($overheads as $key => $value) {
                /* check if record already exist */
                $tmp = $entityManager->getRepository('MyEconomic\Entity\Overheads')->findOneBy(array('user' => $user, 'year' => $key));
                $overheadsRecord = $tmp ? $tmp : new Overheads();

                /* fill fields with data */
                $overheadsRecord->setUser($user);
                $overheadsRecord->setYear($key);
                $overheadsRecord->setOverheads(json_encode($value));

                /* save */
                $entityManager->persist($overheadsRecord);
                $entityManager->flush();
            }
        }

        /* Get Depreciation/Amortisation */
        $keyFigureCodeHundlers = $client->KeyFigureCode_FindByNumber(array('number' => '6'))->KeyFigureCode_FindByNumberResult;
        $accs = $client->KeyFigureCode_GetAccounts(array('keyFigureCodeHandle' => $keyFigureCodeHundlers))->KeyFigureCode_GetAccountsResult;
        
        if ($accs->AccountHandle) {
            /* Get Depreciation/Amortisation array for every month */
            foreach ($accountPeriods as $year) {
                $tmpPeriod = array();
                foreach ($year['SubPeriods'] as $period) {
                    $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($tmpPeriod, $tmp);
                }
                $depreciations[$year['Year']] = $tmpPeriod;
            }

            /* Save Depreciation/Amortisation in local database */
            foreach ($depreciations as $key => $value) {
                /* check if record already exist */
                $tmp = $entityManager->getRepository('MyEconomic\Entity\Depreciation')->findOneBy(array('user' => $user, 'year' => $key));
                $depreciationsRecord = $tmp ? $tmp : new Depreciation();

                /* fill fields with data */
                $depreciationsRecord->setUser($user);
                $depreciationsRecord->setYear($key);
                $depreciationsRecord->setDepreciation(json_encode($value));

                /* save */
                $entityManager->persist($depreciationsRecord);
                $entityManager->flush();
            }
        }

        /* Get Financial Items */
        $keyFigureCodeHundlers = $client->KeyFigureCode_FindByNumber(array('number' => '7'))->KeyFigureCode_FindByNumberResult;
        $accs = $client->KeyFigureCode_GetAccounts(array('keyFigureCodeHandle' => $keyFigureCodeHundlers))->KeyFigureCode_GetAccountsResult;
        
        if ($accs->AccountHandle) {
            /* Get Financial Items array for every month */
            foreach ($accountPeriods as $year) {
                $tmpPeriod = array();
                foreach ($year['SubPeriods'] as $period) {
                    $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($tmpPeriod, $tmp);
                }
                $financialItems[$year['Year']] = $tmpPeriod;
            }

            /* Save Financial Items in local database */
            foreach ($financialItems as $key => $value) {
                /* check if record already exist */
                $tmp = $entityManager->getRepository('MyEconomic\Entity\FinancialItems')->findOneBy(array('user' => $user, 'year' => $key));
                $financialItemsRecord = $tmp ? $tmp : new FinancialItems();

                /* fill fields with data */
                $financialItemsRecord->setUser($user);
                $financialItemsRecord->setYear($key);
                $financialItemsRecord->setFinancialItems(json_encode($value));

                /* save */
                $entityManager->persist($financialItemsRecord);
                $entityManager->flush();
            }
        }

        /* Get Extraordinary Items */
        $keyFigureCodeHundlers = $client->KeyFigureCode_FindByNumber(array('number' => '8'))->KeyFigureCode_FindByNumberResult;
        $accs = $client->KeyFigureCode_GetAccounts(array('keyFigureCodeHandle' => $keyFigureCodeHundlers))->KeyFigureCode_GetAccountsResult;
        
        if ($accs->AccountHandle) {
            /* Get Extraordinary Items array for every month */
            foreach ($accountPeriods as $year) {
                $tmpPeriod = array();
                foreach ($year['SubPeriods'] as $period) {
                    $tmp = abs(array_sum($client->Account_GetEntryTotalsByDate(array('accounts' => $accs->AccountHandle, 'first' => $period['FromDate'], 'last' => $period['ToDate']))->Account_GetEntryTotalsByDateResult->decimal));
                    array_push($tmpPeriod, $tmp);
                }
                $extraordinaryItems[$year['Year']] = $tmpPeriod;
            }

            /* Save Extraordinary Items in local database */
            foreach ($extraordinaryItems as $key => $value) {
                /* check if record already exist */
                $tmp = $entityManager->getRepository('MyEconomic\Entity\ExtraordinaryItems')->findOneBy(array('user' => $user, 'year' => $key));
                $extraordinaryItemsRecord = $tmp ? $tmp : new ExtraordinaryItems();

                /* fill fields with data */
                $extraordinaryItemsRecord->setUser($user);
                $extraordinaryItemsRecord->setYear($key);
                $extraordinaryItemsRecord->setExtraordinaryItems(json_encode($value));

                /* save */
                $entityManager->persist($extraordinaryItemsRecord);
                $entityManager->flush();
            }
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
        $turnoverLast = array();
        foreach($turnoverAll as $turnover){
            array_push($years, $turnover->getYear());
            $tmp = json_decode($turnover->getTurnover());
            $total = array_sum($tmp);
            array_push($turnoverTotal, $total);
            if($turnover->getYear() == $year || $turnover->getYear() == $year-1){
                array_push($turnoverAverage, $total/12);

            };
            if($turnover->getYear()==$year){
                $turnoverThis=$tmp;
            }elseif($turnover->getYear()==$year-1){
                $turnoverLast=$tmp;
            }
        }

        if (!$turnoverLast) {
            array_unshift($turnoverAverage, 0);
            $turnoverLast = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
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

    /********************************
     * Show page with Contribution Margin charts
     *******************************/
    public function contributionMarginAction(){
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
        $variableCostsAll = $entityManager->getRepository('MyEconomic\Entity\VariableCosts')->findBy(array('user'=>$user));

        $years = array();
        $contributionMarginTotal = array();
        $contributionMarginAverage = array();
        $contributionMarginLast = array();
        foreach($turnoverAll as $key => $turnover){
            array_push($years, $turnover->getYear());
            $tmp = json_decode($turnover->getTurnover());
            $tmp2 = json_decode($variableCostsAll[$key]->getVariableCosts());
            $total = array_sum($tmp) - array_sum($tmp2);
            array_push($contributionMarginTotal, $total);
            if($turnover->getYear()==$year || $turnover->getYear()==$year-1){
                array_push($contributionMarginAverage, $total/12);
            };
            if($turnover->getYear()==$year){
                foreach ($tmp as $tmpkey => $value) {
                    $contributionMarginThis[] = $value - $tmp2[$tmpkey];
                }      
            } elseif ($turnover->getYear()==$year-1) {
                foreach ($tmp as $tmpkey => $value) {
                    $contributionMarginLast[] = $value - $tmp2[$tmpkey];
                }
            }
        }

        if (!$contributionMarginLast) {
            array_unshift($contributionMarginAverage, 0);
            $contributionMarginLast = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        }

        $dif = array();
        foreach($contributionMarginThis as $key => $val){
            if ($val < $contributionMarginLast[$key]) {
                array_push($dif, $key);
            }
        }

        return array(
            "year"                      => (int)$year,
            "contributionMarginThis"    => $contributionMarginThis,
            "contributionMarginLast"    => $contributionMarginLast,
            "dif"                       => $dif,
            "years"                     => $years,
            "contributionMarginTotal"   => $contributionMarginTotal,
            "contributionMarginAverage" => $contributionMarginAverage);
    }

    /********************************
     * Show page with Contribution Margin % charts
     *******************************/
    public function contributionMarginPercentagesAction(){
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
        $variableCostsAll = $entityManager->getRepository('MyEconomic\Entity\VariableCosts')->findBy(array('user'=>$user));

        $years = array();
        $contributionMarginTotal = array();
        $contributionMarginAverage = array();
        $contributionMarginLast = array();
        foreach($turnoverAll as $key => $turnover){
            array_push($years, $turnover->getYear());
            $tmp = json_decode($turnover->getTurnover());
            $tmp2 = json_decode($variableCostsAll[$key]->getVariableCosts());
            if (array_sum($tmp)) {
                $total = (array_sum($tmp) - array_sum($tmp2)) / array_sum($tmp);
            } else {
                $total = 0;
            }            
            array_push($contributionMarginTotal, $total);
            if($turnover->getYear()==$year || $turnover->getYear()==$year-1){
                array_push($contributionMarginAverage, $total/12);
            };
            if($turnover->getYear()==$year){
                foreach ($tmp as $tmpkey => $value) {
                    if ($value) {
                        $contributionMarginThis[] = ($value - $tmp2[$tmpkey]) / $value;
                    } else {
                        $contributionMarginThis[] = 0;
                    }                    
                }      
            } elseif ($turnover->getYear()==$year-1) {
                foreach ($tmp as $tmpkey => $value) {
                    if ($value) {
                        $contributionMarginLast[] = ($value - $tmp2[$tmpkey]) / $value;
                    } else {
                        $contributionMarginLast[] = 0;
                    }                    
                }
            }
        }

        if (!$contributionMarginLast) {
            array_unshift($contributionMarginAverage, 0);
            $contributionMarginLast = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        }

        $dif = array();
        foreach($contributionMarginThis as $key => $val){
            if ($val < $contributionMarginLast[$key]) {
                array_push($dif, $key);
            }
        }

        return array(
            "year"                      => (int)$year,
            "contributionMarginThis"    => $contributionMarginThis,
            "contributionMarginLast"    => $contributionMarginLast,
            "dif"                       => $dif,
            "years"                     => $years,
            "contributionMarginTotal"   => $contributionMarginTotal,
            "contributionMarginAverage" => $contributionMarginAverage);
    }

    /********************************
     * Show page with Result charts
     *******************************/
    public function resultAction(){
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

        $financialItemsAll = $entityManager->getRepository('MyEconomic\Entity\FinancialItems')->findBy(array('user'=>$user));
        $extraordinaryItemsAll = $entityManager->getRepository('MyEconomic\Entity\ExtraordinaryItems')->findBy(array('user'=>$user));

        $years = array();
        $resultTotal = array();
        $resultAverage = array();
        $resultLast = array();
        foreach($financialItemsAll as $key => $item){
            array_push($years, $item->getYear());
            $tmp = json_decode($item->getFinancialItems());
            $tmp2 = json_decode($extraordinaryItemsAll[$key]->getExtraordinaryItems());
            $total = array_sum($tmp) + array_sum($tmp2);
            array_push($resultTotal, $total);
            if($item->getYear()==$year || $item->getYear()==$year-1){
                array_push($resultAverage, $total/12);
            };
            if($item->getYear()==$year){
                foreach ($tmp as $tmpkey => $value) {
                    $resultThis[] = $value + $tmp2[$tmpkey];
                }      
            } elseif ($item->getYear()==$year-1) {
                foreach ($tmp as $tmpkey => $value) {
                    $resultLast[] = $value + $tmp2[$tmpkey];
                }
            }
        }

        if (!$resultLast) {
            array_unshift($resultAverage, 0);
            $resultLast = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        }

        $dif = array();
        foreach($resultThis as $key => $val){
            if ($val < $resultLast[$key]) {
                array_push($dif, $key);
            }
        }

        return array(
            "year"              => (int)$year,
            "resultThis"        => $resultThis,
            "resultLast"        => $resultLast,
            "dif"               => $dif,
            "years"             => $years,
            "resultTotal"       => $resultTotal,
            "resultAverage"     => $resultAverage);
    }

    /********************************
     * Show page with Profit Margin charts
     *******************************/
    public function profitMarginAction(){
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
        $variableCostsAll = $entityManager->getRepository('MyEconomic\Entity\VariableCosts')->findBy(array('user'=>$user));
        $financialItemsAll = $entityManager->getRepository('MyEconomic\Entity\FinancialItems')->findBy(array('user'=>$user));
        $extraordinaryItemsAll = $entityManager->getRepository('MyEconomic\Entity\ExtraordinaryItems')->findBy(array('user'=>$user));
        $directPayAll = $entityManager->getRepository('MyEconomic\Entity\DirectPay')->findBy(array('user'=>$user));
        $companyTaxAll = $entityManager->getRepository('MyEconomic\Entity\CompanyTax')->findBy(array('user'=>$user));
        $depreciationAll = $entityManager->getRepository('MyEconomic\Entity\Depreciation')->findBy(array('user'=>$user));
        $overheadsAll = $entityManager->getRepository('MyEconomic\Entity\Overheads')->findBy(array('user'=>$user));
        $otherDirectCostsAll = $entityManager->getRepository('MyEconomic\Entity\OtherDirectCosts')->findBy(array('user'=>$user));
        
        $years = array();
        $profitMarginTotal = array();
        $profitMarginAverage = array();
        $profitMarginLast = array();
        foreach($turnoverAll as $key => $turnover){
            array_push($years, $turnover->getYear());
            $tmp = json_decode($turnover->getTurnover());
            $tmp2 = json_decode($variableCostsAll[$key]->getVariableCosts());
            $tmp3 = json_decode($financialItemsAll[$key]->getFinancialItems());
            $tmp4 = json_decode($extraordinaryItemsAll[$key]->getExtraordinaryItems());
            $tmp5 = json_decode($directPayAll[$key]->getDirectPay());
            $tmp6 = json_decode($companyTaxAll[$key]->getCompanyTax());
            $tmp7 = json_decode($depreciationAll[$key]->getDepreciation());
            $tmp8 = json_decode($overheadsAll[$key]->getOverheads());
            $tmp9 = json_decode($otherDirectCostsAll[$key]->getOtherDirectCosts());
            if (array_sum($tmp)) {
                $total = (array_sum($tmp) - array_sum($tmp2) - array_sum($tmp3) - array_sum($tmp4) - array_sum($tmp5) - array_sum($tmp6) - array_sum($tmp7) - array_sum($tmp8) - array_sum($tmp9)) / array_sum($tmp);
            } else {
                $total = 0;
            }            
            array_push($profitMarginTotal, $total);
            if($turnover->getYear()==$year || $turnover->getYear()==$year-1){
                array_push($profitMarginAverage, $total/12);
            };
            if($turnover->getYear()==$year){
                foreach ($tmp as $tmpkey => $value) {
                    if ($value) {
                        $profitMarginThis[] = ($value - $tmp2[$tmpkey] - $tmp3[$tmpkey] - $tmp4[$tmpkey] - $tmp5[$tmpkey] - $tmp6[$tmpkey] - $tmp7[$tmpkey] - $tmp8[$tmpkey] - $tmp9[$tmpkey]) / $value;
                    } else {
                        $profitMarginThis[] = 0;
                    }                    
                }      
            } elseif ($turnover->getYear()==$year-1) {
                foreach ($tmp as $tmpkey => $value) {
                    if ($value) {
                        $profitMarginLast[] = ($value - $tmp2[$tmpkey] - $tmp3[$tmpkey] - $tmp4[$tmpkey] - $tmp5[$tmpkey] - $tmp6[$tmpkey] - $tmp7[$tmpkey] - $tmp8[$tmpkey] - $tmp9[$tmpkey]) / $value;
                    } else {
                        $profitMarginLast[] = 0;
                    }                    
                }
            }
        }

        if (!$profitMarginLast) {
            array_unshift($profitMarginAverage, 0);
            $profitMarginLast = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        }

        $dif = array();
        foreach($profitMarginThis as $key => $val){
            if ($val < $profitMarginLast[$key]) {
                array_push($dif, $key);
            }
        }

        return array(
            "year"                    => (int)$year,
            "profitMarginThis"        => $profitMarginThis,
            "profitMarginLast"        => $profitMarginLast,
            "dif"                     => $dif,
            "years"                   => $years,
            "profitMarginTotal"       => $profitMarginTotal,
            "profitMarginAverage"     => $profitMarginAverage);
    }
}
