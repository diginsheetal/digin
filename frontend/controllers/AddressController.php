<?php

namespace frontend\controllers;

use Yii;
use backend\models\Address;
use backend\models\AddressSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * AddressController implements the CRUD actions for Address model.
 */
class AddressController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Address models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AddressSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Address model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Address model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Address();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->adrid]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Address model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->adrid]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Address model.
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
     * Finds the Address model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Address the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Address::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    public function actionAddaddress()
    {
        //var_dump($_POST);
       
        $userid=Yii::$app->user->identity->id;
        $name=$_POST['username'];
        $email=$_POST['email'];
        $phone=$_POST['phone'];
        $addr1=$_POST['address1'];
        $addr2=$_POST['address2'];
        $city=$_POST['city'];
        $state=$_POST['state'];
        $country=$_POST['country'];       
        $pin=$_POST['pin'];        
        
       /* $success=false;
        $result=array();  
        
        $address=new Address();
        $address->userid=$userid;
        $address->name=$name;
        $address->email=$email;
        $address->phone=$phone;
        $address->address1=$addr1;
        $address->address2=$addr2;
        $address->city=$city;
        $address->state=$state;
        $address->country=$country;
        $address->pin=$pin;
        $address->crtdt=date('Y-m-d H:i:s');
        $address->crtby=$userid;
        $address->upddt=date('Y-m-d H:i:s');
        $address->updby=$userid;      
        $success=$address->save();
       /* if($success)
        {
            $result=["status"=>1,"error"=>''];   
        }        
        else
        {           
           $result=["status"=>0,"error"=>'One or more parameter missing.'];   
        }
        echo json_encode($result);
        $address=Address::find()->where(['userid'=>$userid])->orderBy('upddt DESC')->all();       
        $data=array();
        $result=array();
        foreach ($address as $ad)
        {
            $data['adrid']=$ad['adrid'];
            $data['userid']=$ad['userid'];
            $data['name']=$ad['name'];
            $data['email']=$ad['email'];
            $data['phone']=$ad['phone'];
            $data['address1']=$ad['address1'];
            $data['address2']=$ad['address2'];
            $data['city']=$ad['city'];
            $data['state']=$ad['state'];
            $data['country']=$ad['country'];
            $data['pin']=$ad['pin'];
            array_push($result, $data);
        }*/
       // echo json_encode($result);
        
        
        $address=new \frontend\models\Address();
        $result = $address->insertAddress($userid,$name,$email,$phone,$addr1,$addr2,$city,$state,$country,$pin);
        return $this->render('selectAddress',array('address'=>$result));
    }
    
    public function actionViewaddress()
    {
        $userid=$_GET['userid'];
        //echo $userid."<br>"; 
        /*$address=Address::find()->where(['userid'=>$userid])
                                ->orderBy('upddt DESC')
                                ->all();        
        $data=array();
        $result=array();
        foreach ($address as $ad)
        {
            $data['adrid']=$ad['adrid'];
            $data['userid']=$ad['userid'];
            $data['name']=$ad['name'];
            $data['email']=$ad['email'];
            $data['phone']=$ad['phone'];
            $data['address1']=$ad['address1'];
            $data['address2']=$ad['address2'];
            $data['city']=$ad['city'];
            $data['state']=$ad['state'];
            $data['country']=$ad['country'];
            $data['pin']=$ad['pin'];
            array_push($result, $data);
        }
       // echo json_encode($result);*/
        $address=new \frontend\models\Address();
        $result = $address->showAddress($userid);
        return $this->render('selectAddress',array('address'=>$result));
    }
    
   /* public function actionShowuser()
    {
         $userid=$_GET['userid'];
         
          $query = (new \yii\db\Query()) 
                  ->select(['u.username','u.email','u.phone','a.address1','a.address2','a.city','a.state','a.country','a.pin'])
                  ->from(['user u'])
                  ->join('inner join','address a','u.id=a.userid')
                  ->where(['u.id'=>$userid]);
          $useraddr=$query->all();
          //echo json_encode($useraddr);
         return $this->render('selectAddress',array('address'=>$useraddr));
     }*/
	 
    public function actionAddress()
    {        
        return $this->render('address');
    }
}
