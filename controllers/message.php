<?php
/**
 * @brief 消息模块
 * @class Message
 * @note  后台
 */
class Message extends IController
{
	protected $checkRight  = 'all';
	public $layout='admin';
	private $data = array();

	function init()
	{
		$checkObj = new CheckRights($this);
		$checkObj->checkAdminRights();
	}

	/**
	 * @brief 模板列表
	 */
	function tpl_list()
	{
		$tb_msg_template = new IModel('msg_template');
		$tpls = $tb_msg_template->query();
		$this->data['tpl'] = $tpls;
		$this->setRenderData($this->data);
		$this->redirect('tpl_list');
	}

	//删除电子邮箱订阅
	function registry_del()
	{
		$ids = IFilter::act(IReq::get('id'),'int');
		if(empty($ids))
		{
			$this->redirect('registry_list',false);
			Util::showMessage('请选择要删除的邮箱');
			exit;
		}

		if(is_array($ids))
		{
			$ids = join(',',$ids);
		}

		$registryObj = new IModel('email_registry');
		$registryObj->del('id in ('.$ids.')');
		$this->redirect('registry_list');
	}

	/**
	 * @brief 编辑模板
	 */
	function tpl_edit()
	{
		$tid = intval(IReq::get('tid'));
		if($tid)
		{
			$tb_msg_template = new IModel('msg_template');
			$data_tpl = $tb_msg_template->query('id='.$tid);
			if($data_tpl && is_array($data_tpl) && $info=$data_tpl[0])
			{
				$this->data['tpl'] = $info;
				$this->setRenderData($this->data);
				$this->redirect('tpl_edit');
			}
			else
			{
				$this->redirect('tpl_list');
			}

		}
		else
		{
			$this->redirect('tpl_list');
		}
	}

	/**
	 * @brief 保存模板修改
	 */
	function tpl_save()
	{
		$tid = intval(IReq::get('tpl_id','post'));
		if($tid)
		{
			$title = IFilter::act(IReq::get('title'),'string');
			$content = IFilter::act(IReq::get('content'),'text');
			$tb_msg_template = new IModel('msg_template');
			$tb_msg_template->setData(array('title'=>$title,'content'=>$content));
			$tb_msg_template->update('id='.$tid);
		}
		$this->redirect('tpl_list');
	}


	function registry_list()
	{
		$tb_user_group = new IModel('user_group');
		$data_group = $tb_user_group->query();
		$data_group = is_array($data_group) ? $data_group : array();
		$group      = array();
		foreach($data_group as $value)
		{
			$group[$value['id']] = $value['group_name'];
		}
		$this->data['group'] = $group;
		$this->setRenderData($this->data);

		//获取模板
		$tb_tpl = new IModel("msg_template");
		$tpl = $tb_tpl->getObj("id=2");
		if(!$tpl)
		{
			$tpl = array('name'=>'','title'=>'','content'=>'');
		}
		$this->tpl = $tpl;

		$this->redirect('registry_list');
	}

	/**
	 * 导出参与订阅的email
	 */
	function registry_export()
	{
		$list=array();
		$tb = new IModel("email_registry");

		$ids = IReq::get('ids');
		$ids_sql = "";
		if($ids)
		{
			$ids = explode(",",$ids);
			$ids = IFilter::act($ids,'int');
			$ids = implode(",",$ids);
			$ids_sql = "where id IN ({$ids})";
		}

		$now = date("Y-m-d_H:i");
		//开始生成csv
		header("Content-type:text/csv");
		header("Content-Disposition: attachment; filename=export_{$now}.csv");

		$start = 0;
		$query = new IQuery("email_registry");
		$query->fields = "email";
		$query->order = "id DESC";

		do
		{
			$query->limit = "{$start},1000";
			$list = $query->find();
			$start += 1000;

			$string = Util::array2csv($list);
			echo $string;
			flush();
		}
		while(count($list)>=1000);
		die();
	}


	/**
	 * @brief 发送信件
	 */
	function registry_message_send()
	{
		$smtp  = new SendMail();
		$error = $smtp->getError();

		$list=array();
		$tb = new IModel("email_registry");

		$ids = IReq::get('ids');
		$ids_sql = "";
		if($ids)
		{
			$ids = explode(",",$ids);
			$ids = IFilter::act($ids,'int');
			$ids = implode(",",$ids);
			$ids_sql = "id IN ({$ids})";
		}

		set_time_limit(0);
		$title = IFilter::act(IReq::get('title'));
		$content = IReq::get("content");

		$start = 0;
		$query = new IQuery("email_registry");
		$query->fields = "email";
		$query->order = "id DESC";
		$query->where = $ids_sql;

		do
		{
			$query->limit = "{$start},50";
			$list = $query->find();
			if(count($list) ==0 )
			{
				break;
			}
			$start += 1000;

			$to = array_pop($list);
			$to = $to['email'];
			$bcc = array();
			foreach($list as $value)
			{
				$bcc[] = $value['email'];
			}
			$bcc = implode(";",$bcc);
			$smtp->send($to,$title,$content,$bcc );
		}
		while(count($list)>=50);
		echo "success";
	}
}

