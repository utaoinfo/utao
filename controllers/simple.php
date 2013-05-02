<?php
/**
 * @copyright Copyright(c) 2011 jooyea.net
 * @file Simple.php
 * @brief
 * @author webning
 * @date 2011-03-22
 * @version 0.6
 * @note
 */
/**
 * @brief Simple
 * @class Simple
 * @note
 */
class Simple extends IController
{
    public $layout='site_mini';

	function init()
	{
		$user = array();
		$user['user_id']  = ISafe::get('user_id');
		$user['username'] = ISafe::get('username');
		$user['head_ico'] = ISafe::get('head_ico');
		$this->user = $user;
	}

	function login()
	{
		//如果已经登录，就跳到ucenter页面
		if( ISafe::get('user_id') != null  )
		{
			$this->redirect("/ucenter/index");
		}
		else
		{
			$this->redirect('login');
		}
	}

	//退出登录
    function logout()
    {
    	ISafe::clearAll();
    	$this->redirect('login');
    }

    //用户注册
    function reg_act()
    {
    	$email      = IFilter::act(IReq::get('email','post'));
    	$username   = IFilter::act(IReq::get('username','post'));
    	$password   = IFilter::act(IReq::get('password','post'));
    	$repassword = IFilter::act(IReq::get('repassword','post'));
    	$captcha    = IReq::get('captcha','post');
    	$message    = '';

		/*注册信息校验*/
    	if(IValidate::email($email) == false)
    	{
    		$message = '邮箱格式不正确';
    	}
    	else if(!Util::is_username($username))
    	{
    		$message = '用户名必须是由2-20个字符，可以为字数，数字下划线和中文';
    	}
    	else if(!preg_match('|\S{6,32}|',$password))
    	{
    		$message = '密码必须是字母，数字，下划线组成的6-32个字符';
    	}
    	else if($password != $repassword)
    	{
    		$message = '2次密码输入不一致';
    	}
    	else if($captcha != ISafe::get('Captcha'))
    	{
    		$message = '验证码输入不正确';
    	}
    	else
    	{
    		$userObj = new IModel('user');
    		$where   = 'email = "'.$email.'" or username = "'.$email.'" or username = "'.$username.'"';
    		$userRow = $userObj->getObj($where);

    		if(!empty($userRow))
    		{
    			if($email == $userRow['email'])
    			{
    				$message = '此邮箱已经被注册过，请重新更换';
    			}
    			else
    			{
    				$message = "此用户名已经被注册过，请重新更换";
    			}
    		}
    	}

		//校验通过
    	if($message == '')
    	{
    		//user表
    		$userArray = array(
    			'username' => $username,
    			'password' => md5($password),
    			'email'    => $email,
    		);
    		$userObj->setData($userArray);
    		$user_id = $userObj->add();

    		if($user_id)
    		{
				//member表
	    		$memberArray = array(
	    			'user_id' => $user_id,
	    			'time'    => ITime::getDateTime(),
	    		);
	    		$memberObj = new IModel('member');
	    		$memberObj->setData($memberArray);
	    		$memberObj->add();

	    		//用户私密数据
	    		ISafe::set('username',$username);
	    		ISafe::set('user_id',$user_id);
	    		ISafe::set('user_pwd',$userArray['password']);

				//自定义跳转页面
				$callback = IReq::get('callback') ? urlencode(IReq::get('callback')) : '';
				$this->redirect('/simple/success_info?callback='.$callback);
    		}
    		else
    		{
    			$message = '注册失败';
    		}
    	}

		//出错信息展示
    	if($message != '')
    	{
    		$this->email    = $email;
    		$this->username = $username;

    		$this->redirect('reg',false);
    		Util::showMessage($message);
    	}
    }

    //用户登录
    function login_act()
    {
    	$login_info = IFilter::act(IReq::get('login_info','post'));
    	$password   = IReq::get('password','post');
    	$remember   = IFilter::act(IReq::get('remember','post'));
    	$autoLogin  = IFilter::act(IReq::get('autoLogin','post'));
    	$callback   = IReq::get('callback');
		$message    = '';

    	if($login_info == '')
    	{
    		$message = '请填写用户名或者邮箱';
    	}
		else if(!preg_match('|\S{6,32}|',$password))
    	{
    		$message = '密码格式不正确,请输入6-32个字符';
    	}
    	else
    	{
    		if($userRow = CheckRights::isValidUser($login_info,md5($password)))
    		{
				$this->loginAfter($userRow);

				//记住帐号
				if($remember == 1)
				{
					ICookie::set('loginName',$login_info);
				}

				//自动登录
				if($autoLogin == 1)
				{
					ICookie::set('autoLogin',$autoLogin);
				}

				//自定义跳转页面
				if($callback != null && $callback != '' && $callback!="/simple/reg" && $callback!="/simple/login")
				{
					$this->redirect($callback);
				}
				else
				{
					$this->redirect('/ucenter/index');
				}
    		}
    		else
    		{
    			$message = '用户名和密码不匹配';
    		}
    	}

    	//错误信息
    	if($message != '')
    	{
    		$this->message = $message;
    		$_GET['callback'] = $callback;
    		$this->redirect('login',false);
    	}
    }

	//登录后的处理
    function loginAfter($userRow)
    {
		//用户私密数据
		ISafe::set('user_id',$userRow['id']);
		ISafe::set('username',$userRow['username']);
		ISafe::set('head_ico',$userRow['head_ico']);
		ISafe::set('user_pwd',$userRow['password']);

		//更新最后一次登录时间
		$memberObj = new IModel('member');
		$dataArray = array(
			'last_login' => ITime::getDateTime(),
		);
		$memberObj->setData($dataArray);
		$where     = 'user_id = '.$userRow["id"];
		$memberObj->update($where);
		$memberRow = $memberObj->getObj($where,'exp');

		//根据经验值分会员组
		$groupObj = new IModel('user_group');
		$groupRow = $groupObj->getObj($memberRow['exp'].' between minexp and maxexp ','id','discount','desc');
		if(!empty($groupRow))
		{
			$dataArray = array('group_id' => $groupRow['id']);
			$memberObj->setData($dataArray);
			$memberObj->update('user_id = '.$userRow["id"]);
		}
    }


    //根据goods_id获取货品
    function getProducts()
    {
    	$id = intval(IReq::get('id'));
    	$productObj   = new IModel('products');
    	$productsList = $productObj->query('goods_id = '.$id,'sell_price,id,spec_array,goods_id','store_nums','desc',7);
		if(!empty($productsList))
		{
			$data = array('mod' => 'selectProduct','productList' => $productsList);
			$this->redirect('/block/site',false,$data);
		}
		else
		{
			echo '';
		}
    }



    function do_find_password()
	{
		$username = IReq::get('username');
		if($username === null || !Util::is_username($username)  )
		{
			die("请输入正确的用户名");
		}

		$useremail = IReq::get("useremail");
		if($useremail ===null || !IValidate::email($useremail ))
		{
			die("请输入正确的邮箱地址");
		}

		$captcha = IReq::get("captcha");
		if($captcha != ISafe::get('Captcha'))
		{
			die('验证码输入不正确');
		}

		$tb_user = new IModel("user");
		$username = IFilter::act($username);
		$useremail = IFilter::act($useremail);
		$user = $tb_user->query("username='{$username}' AND email='{$useremail}'");
		if(!$user)
		{
			die("没有这个用户");
		}
		$user=end($user);
		$hash = IHash::md5( microtime(true) .mt_rand());
		$tb_find_password = new IModel("find_password"); //重新生成
		$tb_find_password->setData( array( 'hash'=>$hash ,'user_id'=>$user['id'] , 'addtime'=>time()  ) );

		$sendMail = true;

		if( $tb_find_password->query("`hash` = '{$hash}'") || $tb_find_password->add()  )
		{
			$smtp = new SendMail();

			$url = IUrl::creatUrl("/simple/restore_password/hash/{$hash}");
			$url = IUrl::getHost().$url;
			$content = "请你点击下面这个链接修改密码：<a href='{$url}'>{$url}</a>。<br />如果不能点击，请您把它复制到地址栏中打开。<br />本链接在3天后将自动失效。";

			$re = $smtp->send($user['email'],"您的密码找回",$content );

			if($re===false )
			{
				die("发信失败");
			}
			die("success");
		}
		die("找回密码失败");
	}

	function restore_password()
	{
		$hash = IReq::get("hash");
		if(!$hash)
		{
			throw new IHttpException("参数不完整",0);
			exit;
		}
		$hash = IFilter::act($hash,'string');
		$tb = new IModel("find_password");
		$addtime = time() - 3600*72;
		$row = $tb->getObj("`hash`='$hash' AND addtime>$addtime ");
		if(!$row)
		{
			throw new IHttpException("本链接已失效，请重新申请密码找回链接",0);
			exit;
		}
		$formAction = IUrl::creatUrl("/simple/do_restore_password/hash/$hash");
		$this->formAction = $formAction;
		$this->redirect("restore_password");
	}

	function do_restore_password()
	{
		$hash = IReq::get("hash");
		if(!$hash)
		{
			throw new IHttpException("参数不完整",404);
			exit;
		}
		$hash = IFilter::act($hash,'string');
		$tb = new IModel("find_password");
		$addtime = time() - 3600*72;
		$row = $tb->getObj("`hash`='$hash' AND addtime>$addtime ");
		if(!$row)
		{
			throw new IHttpException("本链接已失效，请重新申请密码找回链接",403);
			exit;
		}

		$pwd = IReq::get("password");
		$repwd = IReq::get("repassword");
		if($pwd == null || strlen($pwd) < 6 || $repwd!=$pwd)
		{
			throw new IHttpException("新密码至少六位，且两次输入的密码应该一致。",403);
			exit;
		}
		$pwd = md5($pwd);
		$tb_user = new IModel("user");
		$tb_user->setData(array("password"=>$pwd));
		$re = $tb_user->update("id='{$row['user_id']}'");
		if($re !== false)
		{
			$message = "修改密码成功";
			$tb->del("`hash`='{$hash}'");
			$this->redirect("/site/success/message/$message");
			exit;
		}
		else
		{
			//throw new IHttpException("修改密码失败",403);
			exit;
		}
	}

    //添加收藏夹
    function favorite_add()
    {
    	$goods_id = intval(IReq::get('goods_id'));
    	$message  = '';

    	if($goods_id == 0)
    	{
    		$message = '商品id值不能为空';
    	}
    	else if(ISafe::get('user_id') == null)
    	{
    		$message = '请先登录';
    	}
    	else
    	{
    		$favoriteObj = new IModel('favorite');
    		$goodsRow    = $favoriteObj->getObj('user_id = '.$this->user['user_id'].' and rid = '.$goods_id);
    		if(!empty($goodsRow))
    		{
    			$message = '您已经收藏过次商品';
    		}
    		else
    		{
    			$catObj = new IModel('category_extend');
    			$catRow = $catObj->getObj('goods_id = '.$goods_id);
    			$cat_id = $catRow ? $catRow['category_id'] : 0;

	    		$dataArray   = array(
	    			'user_id' => $this->user['user_id'],
	    			'rid'     => $goods_id,
	    			'time'    => ITime::getDateTime(),
	    			'cat_id'  => $cat_id,
	    		);
	    		$favoriteObj->setData($dataArray);
	    		$favoriteObj->add();
    		}
    	}

    	if($message == '')
    	{
    		$result = array(
    			'isError' => false,
    			'message' => '收藏成功',
    		);
    	}
    	else
    	{
    		$result = array(
    			'isError' => true,
    			'message' => $message,
    		);
    	}

    	echo JSON::encode($result);
    }

    //获取oauth登录地址
    public function oauth_login()
    {
    	$id = intval(IReq::get('id'));
    	ISafe::set('callback',IReq::get('callback')); //记录回调地址
    	if($id)
    	{
    		$oauthObj = new Oauth($id);
			$result   = array(
				'isError' => false,
				'url'     => $oauthObj->getLoginUrl(),
			);
    		ISession::set('oauth',$id);
    	}
    	else
    	{
			$result   = array(
				'isError' => true,
				'message' => '请选择要登录的平台',
			);
    	}
    	echo JSON::encode($result);
    }

    //获取令牌
    public function oauth_callback()
    {
    	$id = intval(ISession::get('oauth'));
    	if(!$id)
    	{
    		$this->redirect('login');
    		exit;
    	}
    	$oauthObj = new Oauth($id);
    	$result   = $oauthObj->checkStatus($_GET);

    	if($result === true)
    	{
    		$oauthObj->getAccessToken($_GET);
	    	$userInfo = $oauthObj->getUserInfo();

	    	if(isset($userInfo['id']) && isset($userInfo['name']) && $userInfo['id'] != '' &&  $userInfo['name'] != '')
	    	{
	    		$this->bindUser($userInfo,$id);
	    	}
	    	else
	    	{
	    		$this->redirect('login');
	    	}
    	}
    	else
    	{
    		$this->redirect('login');
    	}
    }

    //同步绑定用户数据
    public function bindUser($userInfo,$oauthId)
    {
    	$oauthUserObj = new IModel('oauth_user');
    	$oauthUserRow = $oauthUserObj->getObj("oauth_user_id = '{$userInfo['id']}' and oauth_id = '{$oauthId}' ",'user_id');

    	//没有绑定账号
    	if(empty($oauthUserRow))
    	{
	    	$userObj   = new IModel('user');
	    	$userCount = $userObj->getObj("username = '{$userInfo['name']}'",'count(*) as num');

	    	//没有重复的用户名
	    	if($userCount['num'] == 0)
	    	{
	    		$username = $userInfo['name'];
	    	}
	    	else
	    	{
	    		//随即分配一个用户名
	    		$username = $userInfo['name'].$userCount['num'];
	    	}

	    	ISafe::set('oauth_username',$username);
	    	ISession::set('oauth_id',$oauthId);
	    	ISession::set('oauth_userInfo',$userInfo);

	    	$this->redirect('bind_user');
    	}

    	//存在绑定账号
    	else
    	{
    		$userObj = new IModel('user');
    		$userRow = $userObj->getObj("id = '{$oauthUserRow['user_id']}'");
    		$this->loginAfter($userRow);

			//自定义跳转页面
			$callback = ISafe::get('callback');

			if($callback != null && $callback != '' && $callback!="/simple/reg" && $callback!="/simple/login")
			{
				$this->redirect($callback);
			}
			else
			{
				$this->redirect('/ucenter/index');
			}
    	}
    }

	//绑定已存在用户
    public function bind_exists_user()
    {
    	$login_info     = IReq::get('login_info');
    	$password       = IReq::get('password');
    	$oauth_id       = IFilter::act(ISession::get('oauth_id'));
    	$oauth_userInfo = IFilter::act(ISession::get('oauth_userInfo'));

    	if(!$oauth_id || !isset($oauth_userInfo['id']))
    	{
    		$this->redirect('login');
    		exit;
    	}

    	if($userRow = CheckRights::isValidUser($login_info,md5($password)))
    	{
    		$oauthUserObj = new IModel('oauth_user');

    		//插入关系表
    		$oauthUserData = array(
    			'oauth_user_id' => $oauth_userInfo['id'],
    			'oauth_id'      => $oauth_id,
    			'user_id'       => $userRow['user_id'],
    			'datetime'      => ITime::getDateTime(),
    		);
    		$oauthUserObj->setData($oauthUserData);
    		$oauthUserObj->add();

    		$this->loginAfter($userRow);

			//自定义跳转页面
			$callback = ISafe::get('callback');
			$this->redirect('/simple/success_info/?callback='.$callback);
    	}
    	else
    	{
    		$this->login_info = $login_info;
    		$this->message    = '用户名和密码不匹配';
    		$_GET['bind_type']= 'exists';
    		$this->redirect('bind_user',false);
    	}
    }

	//绑定不存在用户
    public function bind_nexists_user()
    {
    	$username       = IFilter::act(IReq::get('username'));
    	$email          = IFilter::act(IReq::get('email'));
    	$oauth_id       = IFilter::act(ISession::get('oauth_id'));
    	$oauth_userInfo = IFilter::act(ISession::get('oauth_userInfo'));

		/*注册信息校验*/
    	if(IValidate::email($email) == false)
    	{
    		$message = '邮箱格式不正确';
    	}
    	else if(!Util::is_username($username))
    	{
    		$message = '用户名必须是由2-20个字符，可以为字数，数字下划线和中文';
    	}
    	else
    	{
    		$userObj = new IModel('user');
    		$where   = 'email = "'.$email.'" or username = "'.$email.'" or username = "'.$username.'"';
    		$userRow = $userObj->getObj($where);

    		if(!empty($userRow))
    		{
    			if($email == $userRow['email'])
    			{
    				$message = '此邮箱已经被注册过，请重新更换';
    			}
    			else
    			{
    				$message = "此用户名已经被注册过，请重新更换";
    			}
    		}
    		else
    		{
				$userData = array(
					'email'    => $email,
					'username' => $username,
					'password' => md5(ITime::getDateTime()),
				);
				$userObj->setData($userData);
				$user_id = $userObj->add();

				$memberObj  = new IModel('member');
				$memberData = array(
					'user_id'   => $user_id,
					'true_name' => $oauth_userInfo['name'],
					'last_login'=> ITime::getDateTime(),
					'sex'       => isset($oauth_userInfo['sex']) ? $oauth_userInfo['sex'] : 1,
					'time'      => ITime::getDateTime(),
				);
				$memberObj->setData($memberData);
				$memberObj->add();

				$oauthUserObj = new IModel('oauth_user');

				//插入关系表
				$oauthUserData = array(
					'oauth_user_id' => $oauth_userInfo['id'],
					'oauth_id'      => $oauth_id,
					'user_id'       => $user_id,
					'datetime'      => ITime::getDateTime(),
				);
				$oauthUserObj->setData($oauthUserData);
				$oauthUserObj->add();

				$userRow = $userObj->getObj('id = '.$user_id);
				$this->loginAfter($userRow);

				//自定义跳转页面
				$callback = ISafe::get('callback');
				$this->redirect('/simple/success_info/?callback='.$callback);
    		}
    	}

    	if($message != '')
    	{
    		$this->message = $message;
    		$this->redirect('bind_user',false);
    	}
    }
}
