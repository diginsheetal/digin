<?php

namespace frontend\controllers;

use Yii;
use yii\web\Session;
use backend\models\Vendor;
use backend\models\VendorSearch;
use frontend\models\Diginleads;
use backend\models\VendorWorkinghours;
use yii\web\NotFoundHttpException;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use dektrium\user\models\User;
use yii\base\InvalidRouteException;
use mPDF;

class VendorController extends \yii\web\Controller {

    public $enableCsrfValidation = false;

    /*     * **************** email popup on vendor page*************************** */

   public function actionEmailpopup()
		{
			
              $vid = $_POST['vid'];
            if((isset($_POST['email']) && $_POST['email']!="")
			&&  (isset($_POST['subject']) && $_POST['subject']!="")
			&&  (isset($_POST['businessname']) && $_POST['businessname']!="")
			&&  (isset($_POST['message']) && $_POST['message']!="")
			&&  (isset($_POST['name']) && $_POST['name']!="")
			&&  (isset($_POST['phone']) && $_POST['phone']!="")
			&&  (isset($_POST['email1']) && $_POST['email1']!="")
			){
                 
		  $vndr = \backend\models\Vendor::find()->where(['vid'=>$vid])->one();
           $pln = \backend\models\PlanFeatures::find()->where(['featureid'=>12])->andWhere(['planid'=>$vndr['plan']])->one();
          
                $modeldgn = new \frontend\models\Diginleads();
				$modeldgn->vid = $_POST['vid'];
				$modeldgn->leadType = 'Email';
				$modeldgn->leadName = $_POST['name'];
				$modeldgn->leadEmail = $_POST['email1'];
				$modeldgn->leadPhone = $_POST['phone'];
				$modeldgn->crtdt =date('Y-m-d H:i:s');
				$success = $modeldgn->save(false);

                /**********cahnge for as per plan lead email*************/

                                $subject = $_POST['subject'];
				$businessname = $_POST['businessname'];
				$name = $_POST['name'];
				$phone = $_POST['phone'];
				$email1 = $_POST['email1'];
				

			if($pln!=NULL){
				$email = $_POST['email'];
				$message = $_POST['message'];
			}else{
				 $email = 'mail@digin.in'; 
				 $message = $message.'This message was not sent to vendor as his plan does not have lead email. Vendor Name-'.$name;
				}
				
          
			$emailhtm = "<p><b>Enquirer Name:&nbsp;</b>".$name."<br><b>Enquirer Phone No:&nbsp;</b>".$phone."<br><b>Enquirer Email:&nbsp;</b>".$email1."<br><b>Message:&nbsp;</b>".$message."<br><b>BusinessName:&nbsp;</b>".$businessname.""; 
         
           
           \Yii::$app->mailer->compose()                       
                   ->setFrom('mail@digin.in')
                   ->setTo($email)
                   ->setBcc('ghadgepratiksha3@gmail.com')
                   ->setSubject('Customer Contact')
                   //->setTextBody('hi...') 
                   ->setHtmlBody($emailhtm)                  
                   ->send();
             Yii::$app->session->setFlash('success', 'Your mail has been sent Successfully.');  
                } 
              	$this->redirect('index.php?r=search/searchvendors&vid='.$vid);	
		}
    /*     * *********************************Message popup on vendor page*************************************************** */

    public function actionMessagepopup() {
        $phn = \Yii::$app->params['phn'];
        $vid = $_POST['vid'];

        if ((isset($_POST['message']) && $_POST['message'] != "")&& (isset($_POST['businessname']) && $_POST['businessname']!="") && (isset($_POST['phone']) && $_POST['phone'] != "") && (isset($_POST['name']) && $_POST['name'] != "") && (isset($_POST['phone1']) && $_POST['phone1'] != "")) {
            $modeldgn = new \frontend\models\Diginleads();
            $modeldgn->vid = $_POST['vid'];
            $modeldgn->leadType = 'SMS';
            $modeldgn->leadName = $_POST['name'];
            $modeldgn->leadPhone = $_POST['phone1'];
            $modeldgn->crtdt = date('Y-m-d H:i:s');
            $success = $modeldgn->save(false);
            $name = $_POST['name'];
            $message = $_POST['message'];
            $businessname = $_POST['businessname'];

            $vndr = \backend\models\Vendor::find()->where(['vid' => $vid])->one();
            $pln = \backend\models\PlanFeatures::find()->where(['featureid' => 11])->andWhere(['planid' => $vndr['plan']])->one();
            if ($pln != NULL) {
                $phone = $_POST['phone'];
                $phone1 = $_POST['phone1'];
                $message = $_POST['message'];
            } else {
                $phone = $phn;
                $phone1 = $phn;
                $message = 'This message not send to Vendor.:-' . $vndr['businessname'] . 'Email:-' . $vndr['email'] . $message;
            }

            $sms = new \frontend\models\Smssetting();
            $url = $sms->getUrlWithPhone1($message,$businessname, $phone, $name, $phone1);
            $sms->sendMessage($url);
            Yii::$app->session->setFlash('success', 'Your message has been sent Successfully.');
        }
        $this->redirect('index.php?r=search/searchvendors&vid=' . $vid);
    }

    /**
     * Displays the login page.
     * @return string|Response
     */
    public function actionVendorlogin() {

        $model = \Yii::createObject(\dektrium\user\models\LoginForm::className());

        if ($model->load(\Yii::$app->getRequest()->post()) && $model->login()) {

            $user = \dektrium\user\models\User::find()->where(['id' => \Yii::$app->user->identity->id])->one();
            $auth = \Yii::$app->authManager;
            $userRole = $auth->getRolesByUser($user['id']);

            if (array_keys($userRole)[0] == 'Vendor') {
                if (strpos(\Yii::$app->session['userView_returnURL'], 'login') !== false) {
                    \Yii::$app->session->setFlash('info', 'Successfully Login....');
                    $this->redirect('index.php?r=vendor/vendorlayout');
                } else {
                    \Yii::$app->session->setFlash('info', 'Login successfully...');
                    $this->redirect('index.php?r=vendor/vendorlayout');
                }
            } else {
                \Yii::$app->getUser()->logout();
                \Yii::$app->session->setFlash('error', 'You are not allowed to perform this action!');
                $session['flag'] = 0;



                return $this->render('vendorlogin', [
                            'model' => $model,
                            'module' => $this->module,
                ]);
            }
        }
        \Yii::$app->session['userView_returnURL'] = \Yii::$app->getRequest()->referrer;

        return $this->render('vendorlogin', [
                    'model' => $model,
                    'module' => $this->module,
        ]);
    }

      /**
     * displays the vendor dashboard layout
     * $provider :pass the dataprovider to layout page
     * */
    public function actionVendorlayout() {

        $model = new Vendor();
        $userid = \Yii::$app->user->identity->id;
        $todate = date('Y-m-d 23:59:59', strtotime('today'));
        $frmdate = date('Y-m-d 00.00.00', strtotime('today - 29 days'));

        $mainarray = array();
        $productarray = array();
        $vendorimps = 0;
        $totalclicks = 0;
        $ddtt = $frmdate . "_" . $todate;

        if ((isset($_POST['daterange']) && $_POST['daterange'] != "")) {
            $ddtt = $_POST['daterange'];
            $frm = explode('_', $ddtt);
            $frmdate = $frm[0];
            $todate = $frm[1];
        }

        $vendorimps = 0;
        $vendorclicks = 0;
        $userid = \Yii::$app->user->identity->id;

        $query = (new \yii\db\Query())
                ->select(['di.prid', 'di.impressiondate'])
                ->from('digin_impressions di')
                ->join('inner join', 'vendor_products vp', 'vp.prid = di.prid')
                ->join('inner join', 'vendor v', 'v.vid = vp.vid')
                ->where("di.impressiondate>='$frmdate' AND di.impressiondate<='$todate'")
                ->andWhere(['v.user_id' => $userid]);
        $totalimpression = $query->all();
        $vendorimps = sizeof($totalimpression);

        $query1 = (new \yii\db\Query())
                ->select(['o.userid', 'o.crtdt'])
                ->from('orders o')
                ->where("o.crtdt>='$frmdate' AND o.crtdt<='$todate'")
                ->andWhere(['o.userid' => $userid]);
        $totor = $query1->all();
        $totalorder = sizeof($totor);

        $query2 = (new \yii\db\Query())
                ->select(['d.id', 'd.crtdt'])
                ->from('diginleads d')
                ->join('inner join', 'vendor v', 'v.vid = d.vid')
                ->where("d.crtdt>='$frmdate' AND d.crtdt<='$todate'")
                ->andWhere(['v.user_id' => $userid]);
        $totlead = $query2->all();
        $totallead = sizeof($totlead);

        $vpid = \backend\models\Vendor::find()->where(['user_id' => $userid])->one();
        $query3 = (new \yii\db\Query())
                ->select(['dc.vid', 'dc.clickdate'])
                ->from('digin_clicks dc')
                ->join('inner join', 'vendor v', 'dc.vid = v.vid')
                ->where("dc.clickdate>='$frmdate' AND dc.clickdate<='$todate'")
                ->andWhere(['v.user_id' => $userid]);
        $totclick = $query3->all();

        $totalclicks = sizeof($totclick);

        $vendorprodarray = array();
        $query4 = (new \yii\db\Query())
                ->select('p.prid, p.prodname as productname')
                ->from('product p')
                ->join('inner join', 'vendor_products vp', 'vp.prid = p.prid')
                ->join('inner join', 'vendor v', 'v.vid = vp.vid')
                //->where("vp.crtdt>='$frmdate' AND vp.crtdt<='$todate'")
                ->andWhere(['v.user_id' => $userid]);

        $vendorscharttotal = $query4->all();

        $query6 = (new \yii\db\Query())
                ->select('COUNT(di.id) as diginimpression, di.prid')
                ->from('digin_impressions di')
                ->join('inner join', 'vendor_products vp', 'vp.prid = di.prid')
                ->join('inner join', 'vendor v', 'v.vid = vp.vid')
                ->where("di.impressiondate>='$frmdate' AND di.impressiondate<='$todate'")
                ->andWhere(['v.user_id' => $userid])
                ->groupby('di.prid');

        $vendorsproductimpressoin = $query6->all();

        $vpid = \backend\models\Vendor::find()->where(['user_id' => $userid])->one();
        $query5 = (new \yii\db\Query())
                ->select('COUNT(dc.id) as diginclicks, dc.prid')
                ->from('digin_clicks dc')
                ->join('inner join', 'vendor v', 'v.vid = dc.vid')
                ->where("dc.clickdate>='$frmdate' AND dc.clickdate<='$todate'")
                ->andWhere(['dc.vid' => $vpid['vid']])
                ->groupBy('dc.prid');

        $vendorsdiginclicks = $query5->all();

        
        $query4 = (new \yii\db\Query())
                ->select('p.prid,')
                ->from('product p')
                ->join('inner join', 'vendor_products vp', 'vp.prid = p.prid')
                ->join('inner join', 'vendor v', 'v.vid = vp.vid')
                ->andWhere(['v.user_id' => $userid]);

        $prid = $query4->all(); 
        $impressarray1 = array();
        $clicksarray1 = array();
        
        
            foreach ($vendorscharttotal as $vp) {
                $vendorprodarray= array();
                $vendorprodarray['prid'] = $vp['prid'];
                $vendorprodarray['productname'] = $vp['productname'];
                foreach ($vendorsproductimpressoin as $va) {
                    if($va['prid']== $vp['prid'] ){
                     $vendorprodarray['diginimpression'] = $va['diginimpression'];  
//var_dump($vendorprodarray['diginimpression'])	;	
                    }
                }
                if (!isset($vendorprodarray['diginimpression']))
                {
                    $vendorprodarray['diginimpression']="-";
                }
                
                foreach ($vendorsdiginclicks as $vc) {
                    if($vc['prid']== $vp['prid'] ){
                     $vendorprodarray['diginclicks'] = $vc['diginclicks']; 	
//var_dump($vendorprodarray['diginclicks'])	;				 
                    }
                }
                if (!isset($vendorprodarray['diginclicks']))
                {
                    $vendorprodarray['diginclicks']="-";
                }
                array_push($productarray, $vendorprodarray); 
            }

        $provider = new ArrayDataProvider([
            'allModels' => $productarray,
            'key'       => 'prid',
            'sort' => [
                    'attributes' => ['productname'],
                ],
        ]);
        return $this->render('layout', [
                    'dataProvider' => $provider,
                    'vendorimpression' => $vendorimps,
                    'productname' => $vendorscharttotal,
                    'vendorclicks' => $totalclicks,
                    'vendorleads' => $totallead,
                    'vendororders' => $totalorder,
                    'fromdate' => $ddtt,
                    'vendordiginclicks' => $vendorsdiginclicks,
        ]);
    }
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            
              'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],
                'only' => ['index', 'update', 'delete', 'view', 'search','create' ],
                'rules' => [

                    [
                        //'actions' => ['view', 'search'],
                        'allow' => true,
                        'roles' => ['@','Superadmin','Admin','Executive','Franchisee Manager','Franchisee Executive'],
                    ],
                    [
                        'actions' => ['update','index'],
                        'allow' => true,
                        'roles' => ['Vendor'],
                        
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['*','?'],
                    ],
          
                ],
                  
            ],
        ];
    }

    /**
     * Lists all Vendor models.
     * @return mixed
     */
    public function actionIndex()
            
    {
        $auth=Yii::$app->authManager;
        $userRole=$auth->getRolesByUser(Yii::$app->user->identity->id);  
        //var_dump(array_keys($userRole)[0]);        
       
        $searchModel = new VendorSearch();
         if(array_keys($userRole)[0]=='Franchisee Manager')
         {
             $dataProvider = $searchModel->searchByFranchisee(Yii::$app->request->queryParams);
         }
         else if(array_keys($userRole)[0]=='Franchisee Executive' || array_keys($userRole)[0]=='Executive')
         {
             $dataProvider = $searchModel->searchByFranchiseeExecutive(Yii::$app->request->queryParams);
         }
         else{
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
         }
                
        // var_dump($dataProvider);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Vendor model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $VendorWorkinghrs1 = \backend\models\VendorWorkinghours::find()->where(['vid' => $model->vid,'shift'=>'M'])->all();
        //var_dump($VendorWorkinghrs1);
        if($model->shift == 'D')
        {           
            $VendorWorkinghrs2 = \backend\models\VendorWorkinghours::find()->where(['vid' => $model->vid,'shift'=>'E'])->all(); 
        } 
        //var_dump($VendorWorkinghrs2);        
        if($model->shift == 'D')
        {
            return $this->render('view', [
            'model' => $model,
            'VendorWorkinghrs1' => $VendorWorkinghrs1,
            'VendorWorkinghrs2'=> $VendorWorkinghrs2,
        ]);
        }
        else {
            return $this->render('view', [
                   'model' => $model,
                   'VendorWorkinghrs1' => $VendorWorkinghrs1,            
               ]);
        }
    }
    public function saveModels(array $modelarray) {
        $auth=Yii::$app->authManager;
        $userRole=array('');        
        if(isset(Yii::$app->user->identity)){
          $userRole=$auth->getRolesByUser(Yii::$app->user->identity->id);
        }
        $model=$modelarray['model'];
        
        $mdlVendorWorkinghourspost=array();
        $mdlVendorWorkinghours=$modelarray['mdlVendorWorkinghours'];
        //$mdlVendorWorkinghours1=$modelarray['mdlVendorWorkinghours1'];
        $mdlvenfac=$modelarray['mdlvenfac'];
        $mdlVendorPaytype=$modelarray['mdlVendorPaytype'];
        $mdlUser=$modelarray['mdlUser'];
        if(isset($_POST['vendorleadid']) && $_POST['vendorleadid']!=""){
            $vendorleadid = $_POST['vendorleadid'];
        } 
        
       
        $sms=new \backend\models\Smssetting();
        for ($i = 0; $i < 7; $i++) {
            array_push($mdlVendorWorkinghourspost, new VendorWorkinghours());
            
        }
        
        $connection = Yii::$app->db;
        $facsuccess = true;
        $paytsuccess = true;
        $vendwrksuccess = false;
       // $usersuccess=true;
        $usersuccess=false;
        //todo check if uploaded
        $oldlogo=$model->logo;
        $uploadedFile = UploadedFile::getInstance($model, 'logo');
        
        if(($uploadedFile!== null && $uploadedFile!=='' 
                && $uploadedFile->size !== 0 ) 
                || $model->isNewRecord)
        {
            $fileName = $uploadedFile;  //  file name
            $model->logo = $fileName;
        }
        else
        {
            $model->logo=Vendor::findOne($model->vid)->logo;
        }
        
        if(isset($_POST['Vendor']['executive']))
        {
            $model->executive=$_POST['Vendor']['executive'];
            $model->crtby=$model->executive;
            $model->updby=$model->executive;
        }
        //var_dump($model->executive);
        
        if(isset($_POST['Vendor']['franchisee']) && $_POST['Vendor']['franchisee']!="")
        {
            $model->isbyfranchisee=1;
            $model->crtby=$_POST['Vendor']['franchexecutive'];
            $model->updby=$_POST['Vendor']['franchexecutive'];
        }
        else{
             $model->isbyfranchisee=0;
        }
        
        if(isset($_POST['Vendor']['location']))
        {
            $loc= explode(',', $_POST['Vendor']['location']);
            $model->lat=$loc[0];
            $model->lng=$loc[1];
        }

        
       if(array_keys($userRole)[0]=='Franchisee Manager' || array_keys($userRole)[0]=='Franchisee Executive' || Yii::$app->user->isGuest)
       { 
            $model->Is_active=0;
       } 
        
        /*if(isset($_POST['Vendor']['paymentanddelivery']))
        {
            if($_POST['Vendor']['paymentanddelivery']==1)
            {
                $model->payment=1;
                $model->delivery=1;                
            }
            else if($_POST['Vendor']['paymentanddelivery']==2)
            {
                 $model->payment=1;
                 $model->delivery=2;
            }
            else if($_POST['Vendor']['paymentanddelivery']==3)
            {
                 $model->payment=1;
                 $model->delivery=3;
            }
            else if($_POST['Vendor']['paymentanddelivery']==4)
            {
                 $model->payment=0;
                 $model->delivery=3;
            }
        }*/
        
        if(isset($_POST['Vendor']['dppkg']) && $_POST['Vendor']['dppkg']!="")
        {
            $model->dppkgid=$_POST['Vendor']['dppkg'];
        }
        else{ 
             $model->dppkgid=0;
        }
 
        $plan=  \backend\models\Plan::find()->where(['id'=>$model->plan])->one();
        $yr=$plan['year'];
        $enddate=date('Y-m-d', strtotime('+'.$yr.'years'));
        $model->subscriptionenddate=$enddate;

        if(isset($vendorleadid) && $vendorleadid!="") {
        $vendorleadsmodel= \backend\models\VendorLeads::find()->where(['vlid'=>$vendorleadid])->one();
        $vidactiv= \backend\models\Vendorleads::updateAll(['Is_convert'=>1,'conversion_date'=>date('Y-m-d H:i:s'),'Is_converted_by'=> \Yii::$app->user->identity->id], 'vlid='.$vendorleadid);
        }
      
        $transaction = $connection->beginTransaction();
        
        //saves the user model
            $auth = Yii::$app->authManager;
            $mdlUser->username=$_POST['Vendor']['username']; 
            $mdlUser->email=$model->email;
            if(isset($_POST['Vendor']['password_field']) && $_POST['Vendor']['password_field']!=""){
                $mdlUser->password=$_POST['Vendor']['password_field']; 
            }else{
                // Auto generation of password...
                $unm=substr($mdlUser->username, 0, 4);
                $password=$unm.substr($model->phone1, -4);
                $mdlUser->password=$password;
            }
            $mdlUser->confirmed_at = time();
            if(isset($model->phone1))
                $mdlUser->phone=$model->phone1;
            else {
                 $mdlUser->phone='';
            }
            $mdlUser->role='Vendor';
            //var_dump($mdlUser->attributes);
       if($model->isNewRecord){
             $userresult=$this->Checkuser($model->username);
            if(!$userresult){
                $usersuccess=$mdlUser->save(); 
            }else{
                Yii::info("Error: User already exists.");
                $model->addError('username', "Username already exists.");
               // $model->addError('plan', "Please select country again to get plan.");
                echo  $this->render('create', [
                            'model' => $model,
                            'mdlVendorWorkinghours' => $mdlVendorWorkinghours,                            
                            'mdlvenfac' => $mdlvenfac,
                            'mdlVendorPaytype' => $mdlVendorPaytype,]);
                return;
            } 
            }else{
                $usersuccess=$mdlUser->save(); 
            }
        
            $plan=  \backend\models\Plan::find()->where(['id'=>$model->plan])->one();
            if($plan['charge']==0){
                     $model->status='';  
            }else if($_POST['VendorReceivablePayType']['ptypeid']==3)
            {
                    $model->status='Payment Pending';
            }

            if($model->isNewRecord && $usersuccess){        
                $auth->assign($auth->getRole('Vendor'),$mdlUser->id);        
             }        
           $model->user_id=$mdlUser->id; 

           $model->pwd=$mdlUser->password;
           
           /***********************Currencycode Convertion As per thr Countryid******************************/
           $curid = \backend\models\CountryCurrency::findOne(['country_id'=>$plan['countryid']]);
           $currncynm = \backend\models\Currency::findOne(['id'=>$curid['currency_id']]);
           //$model->currencycode="INR";
           $model->currencycode = $currncynm['currency_code'];
          
         // If the vendor model is saved then proceed to save other models. 
       if ($model->save()) {  
            if(isset($_POST['VendorFacilities']['facidarray'])){         
                $mdlvenfac->facidarray = $_POST['VendorFacilities']['facidarray'];
            }
            
            //Delete all the vendor facilities before saving new.
            \backend\models\VendorFacilities::deleteAll(['vid'=>$model->vid]);
            // Save all facilities
            if(isset($mdlvenfac->facidarray) && $mdlvenfac->facidarray!=''){
                foreach ($mdlvenfac->facidarray as $fc) {
                    $fac = new \backend\models\VendorFacilities();
                    $fac->vid = $model->vid;
                    $fac->facid = $fc;
                    $facsuccess&=$fac->save();
                }
            }
            // Save all payment types the vendor accepts
            \backend\models\VendorReceivablePayType::deleteAll(['vid'=>$model->vid]);                     
         
            $mdlVendorPaytype->vid = $model->vid;
            $mdlVendorPaytype->ptypeid=$_POST['VendorReceivablePayType']['ptypeid']; 
            if($mdlVendorPaytype->ptypeid != 1){
                $mdlVendorPaytype->chq_no=$_POST['VendorReceivablePayType']['chq_no'];
                $mdlVendorPaytype->chq_date=$_POST['VendorReceivablePayType']['chq_date'];
            }else{
                $mdlVendorPaytype->chq_no='0000';
                $mdlVendorPaytype->chq_date=0000-00-00;
            }
            $paytsuccess=$mdlVendorPaytype->save();
            // load and save vendor working hours
            $i = 0;
            $wkday=1;
            //VendorWorkinghours::loadMultiple($mdlVendorWorkinghourspost, Yii::$app->request->post());
          
            if (VendorWorkinghours::loadMultiple($mdlVendorWorkinghourspost, Yii::$app->request->post())) {
                $vendwrksuccess=true;
                $choseshift = $model->shift;
               
               \backend\models\VendorWorkinghours::deleteAll(['vid' => $model->vid]);
                foreach ($mdlVendorWorkinghourspost as $mdlVendorWorkinghour) {
                    $venwrkhrs = new VendorWorkinghours();
                                       
                    $venwrkhrs->day =$wkday;
                    $venwrkhrs->isdayoff=$mdlVendorWorkinghour->isdayoff;
                    $venwrkhrs->shift='M';
                    $venwrkhrs->vid = $model->vid;
                     
                    if (!isset($mdlVendorWorkinghour->timefrom) || $mdlVendorWorkinghour->timefrom == '') {
                        $venwrkhrs->timefrom = '00:00';
                    }
                    else
                    {
                        $venwrkhrs->timefrom=$mdlVendorWorkinghour->timefrom;
                    }
                    if (!isset($mdlVendorWorkinghour->timeto) || $mdlVendorWorkinghour->timeto == '') {
                        $venwrkhrs->timeto = '00:00';
                    }
                    else
                    {
                        $venwrkhrs->timeto=$mdlVendorWorkinghour->timeto;
                    }
                 
                    $vendwrksuccess&=$venwrkhrs->save();                    
                    $i++;
                    if ($choseshift == 'D') {
                        $venwrkhrs = new VendorWorkinghours();
                                                                    
                        $venwrkhrs->shift='E';
                        $venwrkhrs->isdayoff=$mdlVendorWorkinghour->isdayoff;
                        $venwrkhrs->vid = $model->vid;
                        $venwrkhrs->day = $wkday;
                        $venwrkhrs->timefrom=$mdlVendorWorkinghour->timefromevening;
                        $venwrkhrs->timeto=$mdlVendorWorkinghour->timetoevening;
                        if (!isset($mdlVendorWorkinghour->timefromevening) || $mdlVendorWorkinghour->timefromevening == '') {
                            $venwrkhrs->timefrom = '00:00';
                        }
                        
                        if (!isset($mdlVendorWorkinghour->timetoevening) || $mdlVendorWorkinghour->timetoevening == '') {
                            $venwrkhrs->timeto = '00:00';
                         
                        }
                     
                            $vendwrksuccess&=$venwrkhrs->save();                           
                             $i++;
                    }
                   
                    $wkday++;
                    }
                    
                }
				
///delivery and payment option coding
  //delete all special_option_delivery records before saving new.
  \backend\models\SpecialDeliveryOption::deleteAll(['vid'=>$model->vid]);    
   
   $spdlvroptn = new \backend\models\SpecialDeliveryOption();

 if(isset($_POST['Vendor']['delivryopt']))
       {
       
 if(isset($_POST['Vendor']['delivryopt'])==3){
           
    if($_POST['kms'] && $_POST['minorderkms']){
           $spdlvroptn1 = new \backend\models\SpecialDeliveryOption();
           $spdlvroptn1->vid = $model->vid;
           $spdlvroptn1->delivery_limit = 'Kms';
           $spdlvroptn1->km_radius = $_POST['kms'];
           $spdlvroptn1->min_order_val = $_POST['minorderkms'];
          
       if(isset($_POST['Vendor']['dlvrsb'])){  
            if($_POST['Vendor']['dlvrsb']==1){
               
               $spdlvroptn1->rest_all =1;
             }else{
               
                $spdlvroptn1->rest_all =0;
              }
          }
           $sccskm=$spdlvroptn1->save();
      
      }
      if($_POST['city'] && $_POST['minordercity']){
           $spdlvroptn2 = new \backend\models\SpecialDeliveryOption();
           $spdlvroptn2->vid = $model->vid;
           $spdlvroptn2->delivery_limit = 'City';
           $spdlvroptn2->km_radius = 0;
           $spdlvroptn2->min_order_val = $_POST['minordercity'];
           
            if(isset($_POST['Vendor']['dlvrsb'])){  
            if($_POST['Vendor']['dlvrsb']==1){
               
               $spdlvroptn2->rest_all =1;
             }else{
               
                $spdlvroptn2->rest_all =0;
              }
          }
           $sccscity=$spdlvroptn2->save();
          
      }
           
      if($_POST['country'] && $_POST['minordercountry']){
           $spdlvroptn3 = new \backend\models\SpecialDeliveryOption();
           $spdlvroptn3->vid = $model->vid;
           $spdlvroptn3->delivery_limit = 'Country';
           $spdlvroptn3->km_radius = 0;
           $spdlvroptn3->min_order_val = $_POST['minordercountry'];
          
          if(isset($_POST['Vendor']['dlvrsb'])){  
            if($_POST['Vendor']['dlvrsb']==1){
               
               $spdlvroptn3->rest_all =1;
             }else{
               
                $spdlvroptn3->rest_all =0;
              }
              $sccscntr=$spdlvroptn3->save();
              
         }
           }
           
       }
      
    }
				
				
				
                // If all the models saved successfully then save the uploaded file
           if ($usersuccess && $facsuccess && $paytsuccess && $vendwrksuccess) {
                if(($uploadedFile!= null && $uploadedFile!='' ) 
                || $model->isNewRecord)
             {
                $fileSavePath = Yii::getAlias("@frontendimagepath") . '/images/vendorlogo/' . $model->vid . '/';
                if (!file_exists($fileSavePath)) {
                    mkdir($fileSavePath, 0755, true);
                }
                $uploadedFile->saveAs($fileSavePath . $fileName);
             }
                $transaction->commit(); 
                $unm=$mdlUser->username;
                $pass=$mdlUser->password;
                                              
           
            $vendor=new Vendor();    
            //Decide Payment Gateway
             $checkpayment=$this->decidePayment($model->vid);
             $paymenttype=  explode('_', $checkpayment);
                if($_POST['vendoreditid']==1){
                    $vid = $model->vid;
                    $phone = $vendor->phone1;
                    $pwd = $mdlUser->password;
                     $sms=new \frontend\models\Smssetting();
                     $url=$sms->getUrlWithPhone($pwd, $phone);
                     $sms->sendMessage($url);  
                    $vendor->sendmail($vid,$model->email,$model->phone1,$mdlUser->username,$mdlUser->password, "");
                    $this->redirect(['/site/index']); 
                    //\Yii::$app->session->setFlash('info', 'An email has been sent with instructions for registering your account and SMS Has been sent to your number.');  
                  
                }else{
                if(Yii::$app->user->isGuest){
                    //\Yii::$app->session->setFlash('info', 'An email has been sent with instructions for registering your account.');                     
                    //$this->redirect(['payment', 'id' => $model->vid]);
                    //if Payment Gateway is payumoney
                   if($paymenttype[0]=="payumoney"){                       
                       $this->redirect(['paymentbypayumoney', 'id' => $model->vid]);
                   }
                   //if Payment Gateway is Paypal
                   else if($paymenttype[0]=="paypal"){                       
                       $this->redirect(['paymentbypaypal', 'id' => $model->vid, 'curr'=>$paymenttype[1]]);
                   }
                }else{ 
                    if($mdlVendorPaytype->ptypeid==3){
                         //\Yii::$app->session->setFlash('info', 'An email has been sent with instructions for registering your account.');  
                         //$this->redirect(['payment', 'id' => $model->vid]);
                         //if Payment Gateway is payumoney
                         if($paymenttype[0]=="payumoney"){
                           // $this->Paymentbypayumoney($model->vid);
                            $this->redirect(['paymentbypayumoney', 'id' => $model->vid]);
                         }
                         //if Payment Gateway is Paypal
                         else if($paymenttype[0]=="paypal"){
                             $this->redirect(['paymentbypaypal', 'id' => $model->vid, 'curr'=>$paymenttype[1]]);
                         }
                    }
                    else{
                        $vendor->Generatepdf($model->vid, $unm);   //$pass
                     \Yii::$app->session->setFlash('info', 'An email has been sent with instructions for registering your account.');  
                    // $this->redirect(['/vendor/index']); 
                   //$this->redirect(['view']); 
                  return $this->redirect(['view', 'id' => $model->vid]);
                    }
                }
           }
              } else {
                $transaction->rollBack();
                Yii::info("Transaction rollbacked...");
                 echo $this->render('create', [
                            'model' => $model,
                            'mdlVendorWorkinghours' => $mdlVendorWorkinghours,                           
                            'mdlvenfac' => $mdlvenfac,
                            'mdlVendorPaytype' => $mdlVendorPaytype,]);  
            } 
            } 
            else
            {                 
                  echo $this->render('create', [
                            'model' => $model,
                            'mdlVendorWorkinghours' => $mdlVendorWorkinghours,                            
                            'mdlvenfac' => $mdlvenfac,
                            'mdlVendorPaytype' => $mdlVendorPaytype,]);
                
            }    
       
        }
        
     /*   public function actionPayment($id)
        {
            $model = $this->findModel($id);
            $plan=  \backend\models\Plan::find()->where(['id'=>$model->plan])->one();
            $paytype=  \backend\models\VendorReceivablePayType::find()->where(['vid'=>$id])->one();
            
            
            if($plan['charge']==0){
                Vendor::updateAll(['pwd'=>''], 'vid='.$id);
                return $this->render('success');
            }            
            else{
            $posted=array();
            // Generate random transaction id
            $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
            $model->txnid=$txnid;
			
	    $model->location=$model->lat.",".$model->lng;
            $user= User::find()->where(['id'=>$model->user_id])->one();
            $model->username=$user->username;            
			
            $model->save();
         
            $paytype->chq_no=$txnid;
            $paytype->save();
			
            $surl='http://digin.in/testing/backend/web/index.php?r=vendorpayment/paymentsuccess';
            $furl='http://digin.in/testing/backend/web/index.php?r=vendorpayment/paymentfailure';          
       //     $surl='https://www.google.co.in/';  
       //     $furl='https://in.yahoo.com/';
            $posted=['key'=>"bO2l7rc5", 'txnid'=>$txnid, 'hash'=>'', 'amount'=>$plan['charge'], 'firstname'=>$model->firstname, 'email'=>$model->email, 'phone'=>$model->phone1, 'productinfo'=>$model->businessname, 'surl'=>$surl, 'furl'=>$furl, 'service_provider'=>'payu_paisa'];
            return $this->render('payment',array('posted'=>$posted));
         }
        }  */

        
        //To decide Payment Gateway
        public function decidePayment($id)
        {
             Yii::info("In VendorController function Decidepayment");
             $model = $this->findModel($id);
             $plan=  \backend\models\Plan::find()->where(['id'=>$model->plan])->one();
             $query=(new yii\db\Query())
                        ->select('c.currency_code')
                        ->from('currency c')
                        ->join('inner join', 'country_currency c1', 'c1.currency_id=c.id')
                        ->where(['c1.country_id'=>$plan['countryid']]);
             $curr=$query->one();
             if($curr['currency_code']!='INR' && $plan['countryid']!=101)
             {
                 return "paypal_".$curr['currency_code'];
             }
             else {
                 return "payumoney_";
             }
        }
        
        public function actionPaymentbypayumoney($id)
        {
            Yii::info("In VendorController function Paymentbypayumoney");
            $model = $this->findModel($id);
            $plan=  \backend\models\Plan::find()->where(['id'=>$model->plan])->one();
            $paytype=  \backend\models\VendorReceivablePayType::find()->where(['vid'=>$id])->one();
            $user= User::find()->where(['id'=>$model->user_id])->one();
            
            //if plan charge i.e amount is Zero
            if($plan['charge']==0){
                Yii::info("Plan charge is zero.");
                $vendor=new Vendor();
                $vendor->sendmail($id, $model->email, $model->phone1, $user->username, $model->pwd, "");
                //Vendor::updateAll(['pwd'=>''], 'vid='.$id);
                return $this->render('success');
            }
            else{
                $posted=array();
                // Generate random transaction id
                $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
                $model->txnid=$txnid;
                $model->currencycode="INR";

                $model->location=$model->lat.",".$model->lng;
                
                $model->username=$user->username;            

                $model->paymentgateway="PayUmoney";
                $model->save();

                $paytype->chq_no=$txnid;
                $paytype->save();

                $surl='http://digin.in/index.php?r=vendorpayment/paymentsuccessforpayumoney'; 
             //   $furl='http://digin.in/advanced/backend/web/index.php?r=vendorpayment/paymentfailureforpayumoney';           
	            $furl='http://digin.in/index.php?r=vendorpayment/paymentfailure'; 
              //  $surl='https://www.google.co.in/';  
              //  $furl='https://in.yahoo.com/';
                //$testkey="bO2l7rc5";
                $posted=['key'=>"ZWmKzvzY", 'txnid'=>$txnid, 'hash'=>'', 'amount'=>$plan['charge'], 'firstname'=>$model->firstname, 'email'=>$model->email, 'phone'=>$model->phone1, 'productinfo'=>$model->businessname, 'surl'=>$surl, 'furl'=>$furl, 'service_provider'=>'payu_paisa'];
                return $this->render('payumoneyform',array('posted'=>$posted));
         }
        }
 
        public function actionPaymentbypaypal($id, $curr)
        {
            Yii::info("In VendorController function Paymentbypaypal");
            $model = $this->findModel($id);
            $plan=  \backend\models\Plan::find()->where(['id'=>$model->plan])->one();
            $paytype=  \backend\models\VendorReceivablePayType::find()->where(['vid'=>$id])->one();
            $user= User::find()->where(['id'=>$model->user_id])->one();          

            if($plan['charge']==0){
                Yii::info("Plan charge is zero.");
                $vendor=new Vendor();
                $vendor->sendmail($id, $model->email, $model->phone1, $user->username, $model->pwd, "");               
                //Vendor::updateAll(['pwd'=>''], 'vid='.$id);

                $model->currencycode=$curr;                
                $model->location=$model->lat.",".$model->lng;                
                $model->username=$user->username;   
                $model->save();
                return $this->render('success');
            }else{                
                $posted=array();
                // Generate random transaction id
                $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
                $model->txnid=$txnid;
                $model->currencycode=$curr;
                
                $model->location=$model->lat.",".$model->lng;
               // $user= User::find()->where(['id'=>$model->user_id])->one();
                $model->username=$user->username;            

                $model->paymentgateway="PayPal";
                
                $model->save();
                
                $paytype->chq_no=$txnid;
                $paytype->save();
                
                $posted=['amount'=>$plan['charge'], 'currcode'=>$curr, 'txnid'=>$txnid];
                return $this->render('paypalform',array('posted'=>$posted));
            }
        }

        public function actionPaytest()
        {
            //var_dump($_REQUEST);
            return $this->render('testsuccess');
        }

        /*** to test sending email  */
  /*   public function actionMailtest()
    {
        echo "Started sending mail";
        Yii::info('Started sending mail...');        
        // $message='<p>Welcome! Your account has been registered at.'.date('Y-m-d H:i:s').' Here is your login credentials!</p>';         
        $unm='bhagyashri'; $pass='bhagyashri123';
        $message='<p>New mail from Digin <br> <b>Subject:&nbsp;</b>Vendor Registration Details<br><b>From:&nbsp;</b>mail@digin.in <br><b>Message:&nbsp;</b>bhagyashri@aayati.co.in Your account has been registered successfully</p><br>'.date('Y-m-d H:i:s').'<br><p>Your login credentials are as follows:<br><b>Username:&nbsp;</b>'.$unm.'<br><b>Password:&nbsp;</b>'.$pass.'</p>';         
        $attachment=  $this->Samplepdf();      
        \Yii::$app->mailer->compose()
                   ->setFrom('mail@digin.in')
                   ->setBcc('sameer@aayati.co.in')     
                   ->setTo('bhagyashri@aayati.co.in')
                   ->setSubject('Vendor Account Registration Details')
                   ->setHtmlBody($message)
                   ->attach($attachment)
                   ->send();
         Yii::info('End sending mail...');       
        echo "End sending mail";  
    }  */

    /**
     * Creates a new Vendor model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Vendor();
        
        $mdlVendorWorkinghours = array();
        $model->shift='S';// Initialize the shift for radio button.
        $mdlvenfac = new \backend\models\VendorFacilities();
        $mdlVendorPaytype = new \backend\models\VendorReceivablePayType();
        //$mdlUser=new \common\models\User();
        $mdlUser= new \dektrium\user\models\User();
        $mdlUser = \Yii::createObject([
            'class'    => \dektrium\user\models\User::className(),
            'scenario' => 'register'
        ]);
       
        for ($i = 0; $i < 7; $i++) {
            array_push($mdlVendorWorkinghours, new VendorWorkinghours());
            
        }
       // 
        if ($model->load(Yii::$app->request->post())) {
            try {
                $this->saveModels(array(
                        'model' => $model,
                        'mdlVendorWorkinghours' => $mdlVendorWorkinghours,
                        //'mdlVendorWorkinghours1' => $mdlVendorWorkinghours1,
                        'mdlvenfac' => $mdlvenfac,
                        'mdlVendorPaytype' => $mdlVendorPaytype,
                        'mdlUser' => $mdlUser,));
                // get uploaded file name to save the Vendor model
            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } else {
            return $this->render('create', [
                        'model' => $model,
                        'mdlVendorWorkinghours' => $mdlVendorWorkinghours,
                        //'mdlVendorWorkinghours1' => $mdlVendorWorkinghours1,
                        'mdlvenfac' => $mdlvenfac,
                        'mdlVendorPaytype' => $mdlVendorPaytype,                       
            ]);
        }
            
    }

    /**
     * Updates an existing Vendor model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $model = $this->findModel($id);
        //$model->username=$model->email;    // did temporary todo...   
        $model->executive=$model->crtby;
        $model->location=$model->lat.','.$model->lng;
        
        if($model->isbyfranchisee==1){
            $model->franchexecutive=$model->crtby;            
        }
        //todo.. user is not updating...
        //$mdlUser= new \dektrium\user\models\User();
       /* $mdlUser = \Yii::createObject([
            'class'    => \dektrium\user\models\User::className(),
            'scenario' => 'update'
        ]);*/  
        $mdlUser=  User::find()->where(['id'=>$model->user_id])->one();
        $mdlUser->scenario="update";
        $model->username=$mdlUser->username;
         
        if($model->payment==1 && $model->delivery==1)
        {
            $model->paymentanddelivery=1;
            $model->dppkg=$model->dppkgid;
        }
        else if($model->payment==1 && $model->delivery==2)
        {
            $model->paymentanddelivery=2;
        }
        else if($model->payment==1 && $model->delivery==3)
        {
            $model->paymentanddelivery=3;
        }
        else if($model->payment==0 && $model->delivery==3)
        {
            $model->paymentanddelivery=4;
        }
        
        $VendorWorkinghrs=array();
        for ($i = 0; $i < 7; $i++) {
            array_push($VendorWorkinghrs, new VendorWorkinghours());            
        }
        
        $VendorWorkinghrsfromdb = \backend\models\VendorWorkinghours::find()->where(['vid' => $model->vid,'shift'=>'M'])->all();
        if(sizeof($VendorWorkinghrsfromdb)>0)
            $VendorWorkinghrs=$VendorWorkinghrsfromdb;
        if($model->shift == 'D')
        {
            $mdlVendorWorkinghours1 = \backend\models\VendorWorkinghours::find()->where(['vid' => $model->vid,'shift'=>'E'])->all();
            $i=0;
            foreach ($VendorWorkinghrs as $ven)
            {
                $ven->timefromevening=$mdlVendorWorkinghours1[$i]->timefrom;
                $ven->timetoevening=$mdlVendorWorkinghours1[$i]->timeto;
                $i++;
            }
        }        
       
        //Get all the vendor facilities models from the database
        $mdlVendorFacility = \backend\models\VendorFacilities::find()->where(['vid' => $model->vid])->all();
        $mdlvenfac = new \backend\models\VendorFacilities();
        // Now add those to newly created models facidarray
        foreach ($mdlVendorFacility as $fac) {
            array_push($mdlvenfac->facidarray, $fac->facid);
        }
        // We also need the names of those facilities so get it from facility table.        
        $facids = implode(',', $mdlvenfac->facidarray);
        $facilityData=array();
        if($facids!=''){
            $facility = \backend\models\Facility::findBySql("select id, name from facility where id IN(" . $facids . ")")->all();
            $facilityData = ArrayHelper::map($facility, 'id', 'name');
        }
        //Get all the vendor paytype models from the database
        $VendorPaytype = \backend\models\VendorReceivablePayType::findAll(['vid' => $model->vid]);
        $mdlVendorPaytype = new \backend\models\VendorReceivablePayType();
        // Now add those to newly created models ptypeidarray
        foreach ($VendorPaytype as $pt) {
            //array_push($mdlVendorPaytype->ptypeidarray, $pt->ptypeid);
            $mdlVendorPaytype->ptypeid=$pt->ptypeid;
            $mdlVendorPaytype->chq_no=$pt->chq_no;
            $mdlVendorPaytype->chq_date=$pt->chq_date;
        }
        // We also need the names of those paytypes so get it from Paymentype table.        
       /* $ptypeids = implode(',', $mdlVendorPaytype->ptypeidarray);
        $paytypes = \backend\models\Paymentype::findBySql("select ptid, type from paymentype where ptid IN(" . $ptypeids . ")")->all();
        $paytypeData = ArrayHelper::map($paytypes, 'ptid', 'type'); */

      //var_dump($VendorWorkinghrs);
        if ($model->load(Yii::$app->request->post()) ) {
            //return $this->redirect(['view', 'id' => $model->vid]);
            $this->saveModels(array(
                        'model' => $model,
                        'mdlVendorWorkinghours' => $VendorWorkinghrs,
                        //'mdlVendorWorkinghours1' => $mdlVendorWorkinghours1,
                        'mdlvenfac' => $mdlvenfac,
                        'mdlVendorPaytype' => $mdlVendorPaytype,
                        'mdlUser' => $mdlUser));
        } else {
            return $this->render('update', [
                        'model' => $model,                       
                        'mdlVendorWorkinghours' =>  $VendorWorkinghrs,
                       // 'mdlVendorWorkinghours1' => $mdlVendorWorkinghours1,
                        'mdlvenfac' => $mdlvenfac,
                        'mdlVendorPaytype' => $mdlVendorPaytype,
                        'venfacilityData' => $facilityData,
                        //'venpaytypeData' => $paytypeData,
            ]);
        } 
    }
     
    
    /**
     * Deletes an existing Vendor model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Vendor model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Vendor the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Vendor::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    public function actionCheck()
    {
        $username=$_POST['username'];        
        $userdata= User::find()->where(['username'=>$username])->all();
       //var_dump(sizeof($userdata));
       //echo $userdata;
        if(sizeof($userdata)==1)
        {
            echo "Username already exists";
        }
        else {
            echo "Username is available";
        }
        //echo "hello...".$username;
        //exit;
    }
    public function Checkuser($uname)
    {
        $userdata= User::find()->where(['username'=>$uname])->one();
       
        if(sizeof($userdata)>0)
        {            
            return true;
        }
        else {            
            return false;
        }        
    }
    public function actionBlockedvendor()
    {
        $vid=$_POST['vid'];
        $block=$_POST['vidblock'];
        
        //echo $vid.".......".$block;
        if($block==0){
        $vidblock=  \backend\models\Vendor::updateAll(['Is_blocked'=>1], 'vid='.$vid);
        }else{
        $vidblock=  \backend\models\Vendor::updateAll(['Is_blocked'=>0], 'vid='.$vid); 
        }
        if($vidblock>0)
            echo "1";
        else{
            echo "0";
        }
        
    }
    
    public function actionActivevendor()
    {
        $vid=$_POST['vid'];
        $activ=$_POST['vidactiv'];
        //echo $vid."..".$activ;
        if($activ==0){
        $vidactiv=  \backend\models\Vendor::updateAll(['Is_active'=>1], 'vid='.$vid);
        }else{
        $vidactiv=  \backend\models\Vendor::updateAll(['Is_active'=>0], 'vid='.$vid); 
        }
         if($vidactiv>0)
            echo "1";
        else{
            echo "0";
        }        
    }
    
    public function actionGetexecutive($id)
    {        
        $query = (new \yii\db\Query()) 
                ->select(['u.user_id','u.firstname','u.lastname'])
                ->from('user_detail u')
                ->join('inner join', 'franchisee_user f', 'f.userid=u.user_id')
                ->where(['u.role'=>'Franchisee Executive'])
                ->andWhere(['f.frid'=>$id]);
       $franchexecutives=$query->all();
       if(sizeof($franchexecutives)>0){
           foreach ($franchexecutives as $fr){            
               echo "<option value='".$fr['user_id']."'>".$fr['firstname']." ".$fr['lastname']."</option>";
           }
       }
       else{
            echo "<option>None</option>";
        }
    }
    
    public function actionGetdeliverypartner()
    {
        $pin=$_GET['pin'];
        $dparray=array();
        $servicabledp=  \backend\models\ServicablePincodes::find()->where(['pincode'=>$pin])->all();
        if(sizeof($servicabledp)>0){           
            foreach ($servicabledp as $s){
                $dpdata=  \backend\models\DeliveryPartner::find()->where(['dpid'=>$s['dpid']])->one();         
                //echo "<option value='".$dpdata['dpid']."'>".$dpdata['name']."</option>";  
                array_push($dparray, ['dpid'=>$dpdata['dpid'], 'name'=>$dpdata['name']]);
            }
        }
        echo json_encode($dparray);
    }

    public function actionGetdeliverypackage($id)
    {
        $dppkg=  \backend\models\Dppackage::find()->where(['dpid'=>$id])->all();
        if(sizeof($dppkg)>0){
            echo "<option value=''>Select</option>";
           foreach ($dppkg as $dp){            
               echo "<option value='".$dp['id']."'>".$dp['packagename']."</option>";
           }
       }
       else{
            echo "<option>None</option>";
        }
    }

    public function actionGetplandesc($id)
    {        
        $plandata= \backend\models\Plan::find()->where(['id'=>$id])->one();
        $query=(new yii\db\Query())
                        ->select('c.currency_code')
                        ->from('currency c')
                        ->join('inner join', 'country_currency c1', 'c1.currency_id=c.id')
                        ->where(['c1.country_id'=>$plandata['countryid']]);
        $curr=$query->one();
        if(sizeof($plandata)==1)
        {
              echo "<b>Plan Description:</b> ".$plandata['description'].
                 "<br><b>Digin Commision(%):</b> ".$plandata['digin_commision'].
                 "<br><b>Plan Charge:</b> ".$curr['currency_code']." ".$plandata['charge'];
        }
        else {
            echo "No plan";
        }        
    }
    
    public function actionGetplan()
    {        
		$country=$_REQUEST['country'];
        $plan=array();
        $countrydata=  \frontend\models\Countries::find()->where(['name'=>$country])->one();
//        echo $countrydata['id'];
        $plandata=  \backend\models\Plan::find()->where(['countryid'=>$countrydata['id']])->all();
        if(sizeof($plandata) > 0)
        {
            foreach ($plandata as $p)
            {
                array_push($plan, ['id'=>$p['id'], 'name'=>$p['name']]);
            }
        }
        echo json_encode($plan);
    }
      
    public function actionGetpackage()
    {
        $pkgid=$_POST['pkgid'];
        $rates=array();
        $packagerates=  \backend\models\Packagerates::find()->where(['pkgid'=>$pkgid])->one();
        $bulkpkgrates=  \backend\models\Bulkrates::find()->where(['pkgid'=>$pkgid])->one();
        if(sizeof($bulkpkgrates)>0)
        {
             $rates=['cityrate'=>$packagerates['withincityrate'], 'zonerate'=>$packagerates['zonerate'], 'metrorate'=>$packagerates['metrorate'], 'RoIArate'=>$packagerates['RoIArate'], 'RoIBrate'=>$packagerates['RoIBrate'], 'spldestrate'=>$packagerates['spldestrate'],
                     'addwithincityrate'=>$packagerates['addwithincityrate'], 'addzonerate'=>$packagerates['addzonerate'], 'addmetrorate'=>$packagerates['addmetrorate'], 'addRoIArate'=>$packagerates['addRoIArate'], 'addRoIBrate'=>$packagerates['addRoIBrate'], 'addspldestrate'=>$packagerates['addspldestrate'],
                     'bulkrate'=>true,
                 'bulkcityrate'=>$bulkpkgrates['withincityrate'], 'bulkzonerate'=>$bulkpkgrates['zonerate'], 'bulkmetrorate'=>$bulkpkgrates['metrorate'], 'bulkRoIArate'=>$bulkpkgrates['RoIArate'], 'bulkRoIBrate'=>$bulkpkgrates['RoIBrate'], 'bulkspldestrate'=>$bulkpkgrates['spldestrate']];
        }else{
            $rates=['cityrate'=>$packagerates['withincityrate'], 'zonerate'=>$packagerates['zonerate'], 'metrorate'=>$packagerates['metrorate'], 'RoIArate'=>$packagerates['RoIArate'], 'RoIBrate'=>$packagerates['RoIBrate'], 'spldestrate'=>$packagerates['spldestrate'],
                    'addwithincityrate'=>$packagerates['addwithincityrate'], 'addzonerate'=>$packagerates['addzonerate'], 'addmetrorate'=>$packagerates['addmetrorate'], 'addRoIArate'=>$packagerates['addRoIArate'], 'addRoIBrate'=>$packagerates['addRoIBrate'], 'addspldestrate'=>$packagerates['addspldestrate'],
                    'bulkrate'=>false,
                    'bulkcityrate'=>'-', 'bulkzonerate'=>'-', 'bulkmetrorate'=>'-', 'bulkRoIArate'=>'-', 'bulkRoIBrate'=>'-', 'bulkspldestrate'=>'-'];
        }
        echo json_encode($rates);        
    }

    public function actionMigratedata()
    {
        $query = new \yii\db\Query;
        $query->select('id,firstname,lastname,avatar,address,city,state,location,location_lat,location_lng,about_me,phone,phone2,zip,Website,shop_name,pay_type')
              ->from('kahev_jsn_users')
              ->where(['not',['id'=>'751']]);
        $data=$query->all();
        //var_dump(sizeof($data));
        $count=0;
        $usersuccess=false;
        $vensuccess=false;
        $psuccess=false;
        $i=0;
        foreach ($data as $dt)
        {
            $vendor=new Vendor();
            $paytype=new \backend\models\VendorReceivablePayType();
             $logo="";
             if($dt['avatar']!='') {
                 $venlogo=  explode("/", $dt['avatar']);
                 $size=  sizeof($venlogo);
                 $count=$size-1;
                 $logo=$venlogo[$count];
            }  
             
            $addr1="";
            $addr2="";
            if($dt['address']!='')
            {
                $address=array();
                $address= explode(",", $dt['address']);
                //var_dump(sizeof($address));     
                if(sizeof($address)>1){
                    $addr1=$address[0].', '.$address[1];
                    if(sizeof($address)>2)
                         $addr2=$address[2];   
                }
                else {
                    $addr1=$address[0];
                }
             }                  
            //echo $addr1.".....".$addr2."<br>";
            $vendortype=strpos($dt['shop_name'],'Pvt. Ltd.')  ? 2 : 1 ;
            //echo $vendortype."<br>"; 
            
            $query->select('username,email')
                  ->from('kahev_users')
                  ->where(['id'=>$dt['id']]);
            $userdata=$query->all();
            
            
           $mdlUser=new \dektrium\user\models\User();
           $auth = Yii::$app->authManager;
            foreach ($userdata as $ud){
               // echo $ud['username'];    
                //saves the user model                
                $mdlUser->username=$ud['username']; 
                $mdlUser->email=$ud['email'];
                $mdlUser->password="reset123"; 
                $mdlUser->confirmed_at = time();
                $mdlUser->phone=$dt['phone'];
                
                //var_dump($mdlUser->attributes);                               
            }
            //var_dump($mdlUser->attributes);
            $usersuccess=$mdlUser->save(false); 
            $auth->assign($auth->getRole('Vendor'),$mdlUser->id);
            $phn="";
            $site="";
            
            $vendor->firstname=$dt['firstname'];
            $vendor->lastname=$dt['lastname'];
            $vendor->email=$mdlUser->email;
           /* if($dt['Website']=="")
                $vendor->website=$site;
            else{ */
                $vendor->website=$dt['Website'];
           // }
            $vendor->businessname=$dt['shop_name'];
            $vendor->logo=$logo;
            $vendor->vendtor_type=$vendortype;
            $vendor->phone1=$dt['phone'];
           /* if($dt['phone2']=="")
                $vendor->phone2=$phn;
            else{ */
                $vendor->phone2=$dt['phone2'];
            //}
            $vendor->aboutme=$dt['about_me'];
            $vendor->address1=$addr1;
            $vendor->address2=$addr2;
            $vendor->city=$dt['city'];
            $vendor->state=$dt['state'];
            if($dt['zip']=="")
                $vendor->pin='00000';
            else {
                $vendor->pin=$dt['zip'];
            }
            $vendor->lat=$dt['location_lat'];
            $vendor->lng=$dt['location_lng'];
            $vendor->googleaddr=$dt['location'];
            $vendor->plan=3;
            $vendor->shift='S';
            $vendor->user_id=$mdlUser->id;
            $vendor->crtdt=date('Y-m-d H:i:s');
            $vendor->crtby=2;
            $vendor->upddt=date('Y-m-d H:i:s');
            $vendor->updby=2;
            //var_dump($vendor->attributes);
            //echo "<br>";
            $vensuccess=$vendor->save(false);
              
            $paytype->vid=$vendor->vid;
            if($dt['pay_type']!="")
                $paytype->ptypeid=$dt['pay_type'];
            else{
                $paytype->ptypeid=0;                
            }
            //var_dump($paytype->attributes);
            $psuccess=$paytype->save();
            if($usersuccess && $vensuccess && $psuccess)
                $i++;
        }
        if($usersuccess && $vensuccess && $psuccess)
        {
            echo "Successfully inserted...".$i;
        }
        else{
            echo "Failed to insert...".$i;
        }    
        
        
     }
        
    public function actionSaveimage()
    {
       //$vid=[1,2,3,4,5]; ->where(['not in','vid',$vid])
       $data= \backend\models\Vendor::find()->select('vid,logo')->all();
       //var_dump($data);
       $i=0;
       foreach ($data as $dt)
       {
           $savefilePath = Yii::getAlias("@frontendimagepath") . '/images/vendorlogo/' . $dt['vid'] . '/';
            if (!file_exists ($savefilePath))
                   mkdir ($savefilePath, 0755, true);
             
           $copyfilePath=Yii::getAlias("@frontendimagepath").'/images_src/profiler/'.$dt['logo'];   
            // echo "......".$copyfilePath."<br>";
             if($dt['logo']!="" && file_exists($copyfilePath)){                
                // echo $copyfilePath."<br>";
                copy($copyfilePath, $savefilePath.$dt['logo']);
                $i++;
             }
       }
       echo $i;
    }
     
    
    public function actionSavenewimage()
    {
       //$vid=[1,2,3,4,5]; ->where(['not in','vid',$vid])
       $data= \backend\models\Vendor::find()->select('vid,logo')->all();
       //var_dump($data);
       $i=0;
       foreach ($data as $dt)
       {
           $savefilePath = Yii::getAlias("@frontendimagepath") . '/images/vendorlogo/' . $dt['vid'] . '/';
                        
           $copyfilePath=Yii::getAlias("@frontendimagepath").'/profiler/'.$dt['logo'];   
            // echo "......".$copyfilePath."<br>";
             if($dt['logo']!="" && file_exists($copyfilePath)){  
                   if (!file_exists ($savefilePath))
                        mkdir ($savefilePath, 0755, true);
                // echo $copyfilePath."<br>";
                copy($copyfilePath, $savefilePath.$dt['logo']);
                $i++;
             }
       }
       echo "Saved logo...".$i;
    }
    
   public function actionVendorcount()
    {
         $favourites=array();  
         $VendorcountModel = new Vendor();
         if((isset($_POST['Vendor']['Date']) && $_POST['Vendor']['Date']!="" )){
         // $ddtt = $_POST['Vendor']['ddtt'];
         $ddtt = ($_POST['Vendor']['Date']);
         //var_dump($ddtt);
         $frm = explode('_',$ddtt);
              
         $query = new \yii\db\Query;  
   
       $query->select('city,count(*),crtdt')
               ->from('vendor')
               ->where("crtdt>='$frm[0]' AND crtdt<='$frm[1]'")
               ->groupBy('city')
               ->all();
         
           $data=$query->all();
          //echo json_encode($data); 
        
        foreach ($data as $dat)
        {
            array_push($favourites, array('City'=>$dat['city'],'Count'=>$dat['count(*)'],'fromdate'=>$frm[0],'todate'=>$frm[1]));  
        }
       
         //echo json_encode($favourites);    
         return $this->render('vendorcount',array('vendorcountmodel'=>$VendorcountModel,'vencount'=>$favourites));
   
       }   
       
        else {
            return $this->render('vendorcount',array('vendorcountmodel'=>$VendorcountModel,'vencount'=>$favourites));
       }
    }


     public function actionContactemail()
      {
           
            if((isset($_POST['subject']) && $_POST['subject']!="")
                &&  (isset($_POST['name']) && $_POST['name']!="")
                &&  (isset($_POST['phone']) && $_POST['phone']!="")
  &&  (isset($_POST['email1']) && $_POST['email1']!="")
                && (isset($_POST['message']) && $_POST['message']!="")){
                
               $modelemail= new \frontend\models\Email();
               
                $modelemail->leadid = 2;
                $modelemail->name = $_POST['name'];
                $modelemail->phone = $_POST['phone'];
                $modelemail->email = $_POST['email1'];
                $modelemail->subject = $_POST['subject'];
                $modelemail->message = $_POST['message'];
                $modelemail->crtdt = date('Y-m-d H:i:s');
                $modelemail->crtby = 1;
                $success = $modelemail->save();                

               $subject = $_POST['subject'];
				$name = $_POST['name'];
				$phone = $_POST['phone'];
				$email1= $_POST['email1'];
				$msg = $_POST['message'];
 
                        $emailsarr = $_POST['email1'];
				$emails = explode(',' , $emailsarr);
				$digemail = 'mail@digin.in';
				
				array_push($emails, $digemail);

				$emailhtm = "<p><b>Enquirer Name:&nbsp;</b>".$name."<br><b>Enquirer Phone No:&nbsp;</b>".$phone."<br><b>Enquirer Email:&nbsp;</b>".$email1."<br><b>Message:&nbsp;</b>".$msg .""; 
           //var_dump($emailhtm);
            \Yii::$app->mailer->compose()                       
                   ->setFrom($email1)
                   ->setTo($emails)
                   ->setBcc('ghadgepratiksha3@gmail.com')
                   ->setSubject($subject)
                   ->setHtmlBody($emailhtm)                  
                   ->send();
             Yii::$app->session->setFlash('success', 'Your mail has been sent Successfully.');  
           } 
              	$this->redirect('index.php');
              
              		
        }
    

      /*****************Message popup on main page************************************* */
	 public function actionContactsms()
      {
        
        if ((isset($_POST['message']) && $_POST['message'] != "")  && (isset($_POST['name']) && $_POST['name'] != "") && (isset($_POST['phone1']) && $_POST['phone1'] != "")) {
                
             $modelsms = new \frontend\models\Sms();
           
            $modelsms->leadid = 1;
            $modelsms->name = $_POST['name'];
            $modelsms->phone = $_POST['phone1'];
            $modelsms->message = $_POST['message'];
            $modelsms->crtdt = date('Y-m-d H:i:s');
            $modelsms->crtby = 1;
            $success = $modelsms->save();

			$name = $_POST['name'];
			$message = $_POST['message'];
			$phone = $_POST['phone1'];
 
		    $sms = new \frontend\models\Smssetting();
            $url = $sms->getUrlWithSms($message, $phone, $name);
            $sms->sendMessage($url);
             Yii::$app->session->setFlash('success', 'Your message has been sent Successfully.');
           } 
             $this->redirect('index.php');      		
        }




}