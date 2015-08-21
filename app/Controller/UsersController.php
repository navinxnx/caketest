<?php
$components=array("Email","Session");
$helpers=array("Html","Form","Session");

class UsersController extends AppController {

	public $paginate = array(
        'limit' => 25,
        'conditions' => array('status' => '1'),
    	'order' => array('User.username' => 'asc' ) 
    );
	
    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow('login','add'); 
    }
	


	public function login() {
		
		//if already logged-in, redirect
		if($this->Session->check('Auth.User')){
			$this->redirect(array('action' => 'index'));		
		}
		
		// if we get the post information, try to authenticate
		if ($this->request->is('post')) {
			if ($this->Auth->login()) {
				$this->Session->setFlash(__('Welcome, '. $this->Auth->user('username')));
				$this->redirect($this->Auth->redirectUrl());
			} else {
				$this->Session->setFlash(__('Invalid username or password'));
			}
		} 
	}

	public function logout() {
		$this->redirect($this->Auth->logout());
	}

    public function index() {
		$this->paginate = array(
			'limit' => 6,
			'order' => array('User.username' => 'asc' )
		);
		$users = $this->paginate('User');
		$this->set(compact('users'));
    }


    public function add() {
        if ($this->request->is('post')) {
				
			$this->User->create();
			if ($this->User->save($this->request->data)) {
				$this->Session->setFlash(__('The user has been created'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be created. Please, try again.'));
			}	
        }
    }

    public function edit($id = null) {

		    if (!$id) {
				$this->Session->setFlash('Please provide a user id');
				$this->redirect(array('action'=>'index'));
			}

			$user = $this->User->findById($id);
			if (!$user) {
				$this->Session->setFlash('Invalid User ID Provided');
				$this->redirect(array('action'=>'index'));
			}

			if ($this->request->is('post') || $this->request->is('put')) {
				$this->User->id = $id;
				if ($this->User->save($this->request->data)) {
					$this->Session->setFlash(__('The user has been updated'));
					$this->redirect(array('action' => 'edit', $id));
				}else{
					$this->Session->setFlash(__('Unable to update your user.'));
				}
			}

			if (!$this->request->data) {
				$this->request->data = $user;
			}
    }

    public function delete($id = null) {
		
		if (!$id) {
			$this->Session->setFlash('Please provide a user id');
			$this->redirect(array('action'=>'index'));
		}
		
        $this->User->id = $id;
        if (!$this->User->exists()) {
            $this->Session->setFlash('Invalid user id provided');
			$this->redirect(array('action'=>'index'));
        }
        if ($this->User->saveField('status', 0)) {
            $this->Session->setFlash(__('User deleted'));
            $this->redirect(array('action' => 'index'));
        }
        $this->Session->setFlash(__('User was not deleted'));
        $this->redirect(array('action' => 'index'));
    }
	
	public function activate($id = null) {
		
		if (!$id) {
			$this->Session->setFlash('Please provide a user id');
			$this->redirect(array('action'=>'index'));
		}
		
        $this->User->id = $id;
        if (!$this->User->exists()) {
            $this->Session->setFlash('Invalid user id provided');
			$this->redirect(array('action'=>'index'));
        }
        if ($this->User->saveField('status', 1)) {
            $this->Session->setFlash(__('User re-activated'));
            $this->redirect(array('action' => 'index'));
        }
        $this->Session->setFlash(__('User was not re-activated'));
        $this->redirect(array('action' => 'index'));
    }

}
//navin code for forget_password

 
	
 
 
 	function forgetpwd(){
 //$this->layout="signup";
 $this->User->recursive=-1;
 if(!empty($this->data))
 {
 if(empty($this->data['User']['email']))
 {
 $this->Session->setFlash('Please Provide Your Email Adress that You used to Register with Us');
 }
 else
 {
 $email=$this->data['User']['email'];
 $fu=$this->User->find('first',array('conditions'=>array('User.email'=>$email)));
 if($fu)
 {
 //debug($fu);
 if($fu['User']['active'])
 {
 $key = Security::hash(String::uuid(),'sha512',true);
 $hash=sha1($fu['User']['username'].rand(0,100));
 $url = Router::url( array('controller'=>'users','action'=>'reset'), true ).'/'.$key.'#'.$hash;
 $ms=$url;
 $ms=wordwrap($ms,1000);
 //debug($url);
 $fu['User']['tokenhash']=$key;
 $this->User->id=$fu['User']['id'];
 if($this->User->saveField('tokenhash',$fu['User']['tokenhash'])){
 
 //============Email================//
 /* SMTP Options */
 $this->Email->smtpOptions = array(
 'port'=>'25',
 'timeout'=>'30',
 'host' => 'mail.example.com',
 'username'=>'accounts+example.com',
 'password'=>'your password'
   );
   $this->Email->template = 'resetpw';
 $this->Email->from    = 'Your Email <accounts@example.com>';
 $this->Email->to      = $fu['User']['name'].'<'.$fu['User']['email'].'>';
 $this->Email->subject = 'Reset Your Example.com Password';
 $this->Email->sendAs = 'both';
 
 $this->Email->delivery = 'smtp';
 $this->set('ms', $ms);
 $this->Email->send();
 $this->set('smtp_errors', $this->Email->smtpError);
 $this->Session->setFlash(__('Check Your Email To Reset your password', true));
 
 //============EndEmail=============//
 }
 else{
 $this->Session->setFlash("Error Generating Reset link");
 }
 }
 else
 {
 $this->Session->setFlash('This Account is not Active yet.Check Your mail to activate it');
 }
 }
 else
 {
 $this->Session->setFlash('Email does Not Exist');
 }
 }
 }
 }
/* function reset($token=null){
 //$this->layout="Login";
 $this->User->recursive=-1;
 if(!empty($token)){
 $u=$this->User->findBytokenhash($token);
 if($u){
 $this->User->id=$u['User']['id'];
 if(!empty($this->data)){
 $this->User->data=$this->data;
 $this->User->data['User']['username']=$u['User']['username'];
 $new_hash=sha1($u['User']['username'].rand(0,100));//created token
 $this->User->data['User']['tokenhash']=$new_hash;
 if($this->User->validates(array('fieldList'=>array('password','password_confirm')))){
 if($this->User->save($this->User->data))
 {
 $this->Session->setFlash('Password Has been Updated');
 $this->redirect(array('controller'=>'users','action'=>'login'));
 }
 
 }
 else{
 
 $this->set('errors',$this->User->invalidFields());
 }
 }
 }
 else
 {
 $this->Session->setFlash('Token Corrupted,,Please Retry.the reset link work only for once.');
 }
 }
 
 else{
 $this->redirect('/');
 }
 }*/
//end navin_code forget_password

?>