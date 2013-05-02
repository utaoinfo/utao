<?php
/**
 * @class Brand
 * @brief 品牌模块
 * @note  后台
 */
class Brand extends IController
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
	 * @brief 修改品牌
	 */
	function brand_edit()
	{
		$brand_id = (int)IReq::get('bid');
		//编辑品牌 读取品牌信息
		if($brand_id)
		{
			$obj_brand = new IModel('brand');
			$brand_info = $obj_brand->query('id='.$brand_id);
			if(is_array($brand_info) && $info=$brand_info[0])
			{
				$this->data['brand'] = array(
					'id'		=>	$info['id'],
					'name'		=>	$info['name'],
					'logo'		=>	$info['logo'],
					'url'		=>	$info['url'],
					'sort'		=>	$info['sort'],
					'description'=>	$info['description']
				);
			}
			else
			{
				$this->category_list();
				Util::showMessage("没有找到相关品牌分类！");
				return;
			}
		}

		$this->setRenderData($this->data);
		$this->redirect('brand_edit',false);
	}

	/**
	 * @brief 保存品牌
	 */
	function brand_save()
	{
		$brand_id = IFilter::act(IReq::get('brand_id'),'int');
		$name = IFilter::act(IReq::get('name'));
		$sort = IFilter::act(IReq::get('sort'),'int');
		$url = IFilter::act(IReq::get('url'));

		$description = IFilter::act(IReq::get('description'),'text');

		$tb_brand = new IModel('brand');
		$brand = array(
			'name'=>$name,
			'sort'=>$sort,
			'url'=>$url,
			'description' => $description,
		);


		if(isset($_FILES['logo']['name']) && $_FILES['logo']['name']!='')
		{
			$uploadObj = new PhotoUpload();
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();
			if(isset($photoInfo['logo']['img']) && file_exists($photoInfo['logo']['img']))
			{
				$brand['logo'] = $photoInfo['logo']['img'];
			}
		}
		$tb_brand->setData($brand);
		if($brand_id)										//保存修改分类信息
		{
			$where = "id=".$brand_id;
			$tb_brand->update($where);
		}
		else												//添加新品牌
		{
			$tb_brand->add();
		}
		$this->brand_list();
	}

	/**
	 * @brief 删除品牌
	 */
	function brand_del()
	{
		$brand_id = (int)IReq::get('bid');
		if($brand_id)
		{
			$tb_brand = new IModel('brand');
			$where = "id=".$brand_id;
			if($tb_brand->del($where))
			{
				$this->brand_list();
			}
			else
			{
				$this->brand_list();
				$msg = "没有找到相关分类记录！";
				Util::showMessage($msg);
			}
		}
		else
		{
			$this->brand_list();
			$msg = "没有找到相关品牌记录！";
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 品牌列表
	 */
	function brand_list()
	{
		$this->redirect('brand_list');
	}
}