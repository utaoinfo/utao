<?php
/**
 * @brief 用户中心模块
 * @class Ucenter
 * @note  前台
 */
class Ucenter extends IController
{
	public $layout = 'ucenter';
	function init()
	{
		$checkObj = new CheckRights($this);
		$checkObj->checkUserRights();

		if(ISafe::get('user_id') == '')
		{
			$this->redirect('/simple/login');
		}
	}
    public function index()
    {
        $this->initPayment();
        $this->redirect('index');

    }

	//[用户头像]上传
	function user_ico_upload()
	{
		$user_id = ISafe::get('user_id');

		$result = array(
			'isError' => true,
		);

		if(isset($_FILES['attach']['name']) && $_FILES['attach']['name'] != '')
		{
			$photoObj = new PhotoUpload();
			$photoObj->setThumb(100,100,'user_ico');
			$photo    = $photoObj->run();

			if(!empty($photo['attach']['thumb']['user_ico']))
			{
				$user_id   = ISafe::get('user_id');

				$user_obj  = new IModel('user');
				$dataArray = array(
					'head_ico' => $photo['attach']['thumb']['user_ico'],
				);
				$user_obj->setData($dataArray);
				$where  = 'id = '.$user_id;
				$isSuss = $user_obj->update($where);

				if($isSuss !== false)
				{
					$result['isError'] = false;
					$result['data'] = IUrl::creatUrl().$photo['attach']['thumb']['user_ico'];
					ISafe::set('head_ico',$dataArray['head_ico']);
				}
				else
				{
					$result['message'] = '上传失败';
				}
			}
			else
			{
				$result['message'] = '上传失败';
			}
		}
		else
		{
			$result['message'] = '请选择图片';
		}
		echo '<script type="text/javascript">parent.callback_user_ico('.JSON::encode($result).');</script>';
	}

 
    /**
     * @brief 建议中心
     */
    public function complain_edit()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $title = IFilter::act(IReq::get('title'),'string');
        $content = IFilter::act(IReq::get('content'),'string' );
        $user_id = $this->user['user_id'];
        $model = new IModel('suggestion');
        $model->setData(array('user_id'=>$user_id,'title'=>$title,'content'=>$content,'time'=>date('Y-m-d H:i:s')));
        if($id =='')
        {
            $model->add();
        }
        else
        {
            $model->update('id = '.$id);
        }
        $this->redirect('complain');
    }
    /**
     * @brief 删除消息
     * @param int $id 消息ID
     */
    public function message_del()
    {
        $id = IFilter::act( IReq::get('id') ,'int' );
        $msg = new Mess($this->user['user_id']);
        $msg->delMessage($id);
        $this->redirect('message');
    }
    public function message_read()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $msg = new Mess($this->user['user_id']);
        echo $msg->writeMessage($id,1);
    }

    //[修改密码]修改动作
    function password_edit()
    {
    	$user_id    = $this->user['user_id'];

    	$fpassword  = IReq::get('fpassword');
    	$password   = IReq::get('password');
    	$repassword = IReq::get('repassword');

    	$userObj    = new IModel('user');
    	$where      = 'id = '.$user_id;
    	$userRow    = $userObj->getObj($where);

		if(!preg_match('|\w{6,32}|',$password))
		{
			$message = '密码格式不正确，请重新输入';
		}
    	else if($password != $repassword)
    	{
    		$message  = '二次密码输入的不一致，请重新输入';
    	}
    	else if(md5($fpassword) != $userRow['password'])
    	{
    		$message  = '原始密码输入错误';
    	}
    	else
    	{
	    	$dataArray = array(
	    		'password' => md5($password),
	    	);

	    	$userObj->setData($dataArray);
	    	$result  = $userObj->update($where);
	    	$message = ($result === false) ? '密码修改失败' : '密码修改成功';
		}

    	$this->redirect('password',false);
    	Util::showMessage($message);
    }

    //[个人资料]展示 单页
    function info()
    {
    	$user_id = $this->user['user_id'];

    	$userObj       = new IModel('user');
    	$where         = 'id = '.$user_id;
    	$this->userRow = $userObj->getObj($where);

    	$memberObj       = new IModel('member');
    	$where           = 'user_id = '.$user_id;
    	$this->memberRow = $memberObj->getObj($where);

    	$userGroupObj       = new IModel('user_group');
    	$where              = 'id = '.$this->memberRow['group_id'];
    	$this->userGroupRow = $userGroupObj->getObj($where);

    	$this->redirect('info');
    }

    //[个人资料] 修改 [动作]
    function info_edit_act()
    {
    	$user_id   = $this->user['user_id'];

    	$memberObj = new IModel('member');
    	$where     = 'user_id = '.$user_id;

		//出生年月
    	$year  = IFilter::act( IReq::get('year','post'),'int' );
    	$month = IFilter::act( IReq::get('month','post'),'int' );
    	$day   = IFilter::act( IReq::get('day','post'),'int' );
    	$birthday = $year.'-'.$month.'-'.$day;

    	//地区
    	$province = IFilter::act( IReq::get('province','post') ,'string');
    	$city     = IFilter::act( IReq::get('city','post') ,'string' );
    	$area     = IFilter::act( IReq::get('area','post') ,'string' );
    	$areaStr  = ','.$province.','.$city.','.$area.',';

    	$dataArray       = array(
    		'true_name'    => IFilter::act( IReq::get('true_name') ,'string'),
    		'sex'          => IFilter::act( IReq::get('sex'),'int' ),
    		'birthday'     => $birthday,
    		'zip'          => IFilter::act( IReq::get('zip') ,'string' ),
    		'msn'          => IFilter::act( IReq::get('msn') ,'string' ),
    		'qq'           => IFilter::act( IReq::get('qq') , 'string' ),
    		'contact_addr' => IFilter::act( IReq::get('contact_addr'), 'string'),
    		'mobile'       => IFilter::act( IReq::get('mobile'), 'string'),
    		'telephone'    => IFilter::act( IReq::get('telephone'),'string'),
    		'area'         => $areaStr,
    	);

    	$memberObj->setData($dataArray);
    	$memberObj->update($where);
    	$this->info();
    }


    //[收藏夹]备注信息
    function edit_summary()
    {
    	$user_id = $this->user['user_id'];

    	$id      = IFilter::act( IReq::get('id'),'int' );
    	$summary = IFilter::act( IReq::get('summary'),'string' );

    	//ajax返回结果
    	$result  = array(
    		'isError' => true,
    	);

    	if(!$id)
    	{
    		$result['message'] = '收藏夹ID值丢失';
    	}
    	else if(!$summary)
    	{
    		$result['message'] = '请填写正确的备注信息';
    	}
    	else
    	{
	    	$favoriteObj = new IModel('favorite');
	    	$where       = 'id = '.$id.' and user_id = '.$user_id;

	    	$dataArray   = array(
	    		'summary' => $summary,
	    	);

	    	$favoriteObj->setData($dataArray);
	    	$is_success = $favoriteObj->update($where);

	    	if($is_success === false)
	    	{
	    		$result['message'] = '更新信息错误';
	    	}
	    	else
	    	{
	    		$result['isError'] = false;
	    	}
    	}
    	echo JSON::encode($result);
    }

    //[收藏夹]获取收藏夹数据
	function get_favorite(&$favoriteObj)
    {
		//获取收藏夹信息
	    $page = (isset($_GET['page'])&&(intval($_GET['page'])>0))?intval($_GET['page']):1;

		$favoriteObj = new IQuery("favorite");
		$cat_id = intval(IReq::get('cat_id'));
		$where = '';
		if($cat_id != 0)
		{
			$where = ' and cat_id = '.$cat_id;
		}
		$favoriteObj->where = "user_id = ".$this->user['user_id'].$where;
		$favoriteObj->page  = $page;
		$items = $favoriteObj->find();

		$goodsIdArray   = array();
		foreach($items as $val)
		{
			$goodsIdArray[] = $val['rid'];
		}

		//商品数据
		if(!empty($goodsIdArray))
		{
			$goodsIdStr = join(',',$goodsIdArray);
			$goodsObj   = new IModel('goods');
			$goodsList  = $goodsObj->query('id in ('.$goodsIdStr.')');
		}

		foreach($items as $key => $val)
		{
			foreach($goodsList as $gkey => $goods)
			{
				if($goods['id'] == $val['rid'])
				{
					$items[$key]['data'] = $goods;

					//效率考虑,让goodsList循环次数减少
					unset($goodsList[$gkey]);
				}
			}

			//如果相应的商品或者货品已经被删除了，
			if(!isset($items[$key]['data']))
			{
				$favoriteModel = new IModel('favorite');
				$favoriteModel->del("id={$val['id']}");
				unset($items[$key]);
			}
		}
		return $items;
    }

    //[收藏夹]删除
    function favorite_del()
    {
    	$user_id = $this->user['user_id'];
    	$id      = IReq::get('id');

		if(!empty($id))
		{
			$id = Util::intval_value($id);

			$favoriteObj = new IModel('favorite');

			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = 'user_id = '.$user_id.' and id in ('.$idStr.')';
			}
			else
			{
				$where = 'user_id = '.$user_id.' and id = '.$id;
			}

			$favoriteObj->del($where);
			$this->redirect('favorite');
		}
		else
		{
			$this->redirect('favorite',false);
			Util::showMessage('请选择要删除的数据');
		}
    }

    //[我的积分] 单页展示
    function integral()
    {
    	/*获取积分增减的记录日期时间段*/
    	$this->historyTime = IFilter::string( IReq::get('history_time','post') );
    	$defaultMonth = 3;//默认查找最近3个月内的记录

		$lastStamp    = ITime::getTime(ITime::getNow('Y-m-d')) - (3600*24*30*$defaultMonth);
		$lastTime     = ITime::getDateTime('Y-m-d',$lastStamp);

		if($this->historyTime != null && $this->historyTime != 'default')
		{
			$historyStamp = ITime::getDateTime('Y-m-d',($lastStamp - (3600*24*30*$this->historyTime)));
			$this->c_datetime = 'datetime >= "'.$historyStamp.'" and datetime < "'.$lastTime.'"';
		}
		else
		{
			$this->c_datetime = 'datetime >= "'.$lastTime.'"';
		}

    	$memberObj         = new IModel('member');
    	$where             = 'user_id = '.$this->user['user_id'];
    	$this->memberRow   = $memberObj->getObj($where,'point');
    	$this->redirect('integral',false);
    }

 
}
?>
