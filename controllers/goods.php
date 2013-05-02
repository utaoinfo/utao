<?php
/**
 * @brief 商品模块
 * @class Goods
 * @note  后台
 */
class Goods extends IController
{
	protected $checkRight  = 'all';
    public $layout = 'admin';
    private $data = array();

	function init()
	{
		if(IReq::get('action') == 'goods_img_upload')
		{
			$admin_name = IFilter::act(IReq::get('admin_name'));
			$admin_pwd  = IFilter::act(IReq::get('admin_pwd'));

			$adminObj = new IModel('admin');
			$adminRow = $adminObj->getObj("admin_name = '".$admin_name."'",'password');
			if(empty($adminRow) || ($adminRow['password'] != $admin_pwd))
			{
				exit;
			}
		}
		else
		{
			$checkObj = new CheckRights($this);
			$checkObj->checkAdminRights();
		}
	}
	/**
	 * @brief 商品添加中图片上传的方法
	 */
	static function goods_img_upload()
	{
		//获得配置文件中的数据
		$config      = new Config("site_config");
		$config_info = $config->getInfo();

		$list_thumb_width  = isset($config_info['list_thumb_width'])  ? $config_info['list_thumb_width']  : 175;
	 	$list_thumb_height = isset($config_info['list_thumb_height']) ? $config_info['list_thumb_height'] : 175;
	 	$show_thumb_width  = isset($config_info['show_thumb_width'])  ? $config_info['show_thumb_width']  : 85;
		$show_thumb_height = isset($config_info['show_thumb_height']) ? $config_info['show_thumb_height'] : 85;

	 	//调用文件上传类
		$photoObj = new PhotoUpload();
		$photoObj->setThumb($show_thumb_width,$show_thumb_height,'show');
		$photoObj->setThumb($list_thumb_width,$list_thumb_height,'list');
		$photo    = $photoObj->run();
		//判断上传是否成功，如果float=1则成功
		if($photo['Filedata']['flag']==1)
		{
			$list = $photo['Filedata']['thumb']['list'];
			$list = strrchr($list,'/');
			$id = substr($list,1,strpos($list,'_')-1);
			$show = $photo['Filedata']['thumb']['show'];
			$img = $photo['Filedata']['img'];
			echo IUrl::creatUrl().$show.'|'.$show.'|'.$img.'|'.$id.'|'.$photo['Filedata']['thumb']['list'].'|'.'_'.$show_thumb_width.'_'.$show_thumb_height;
			exit;
		}
		else
		{
			echo '0';
			exit;
		}
	}

	/**
	 * @brief 修改的商品排序
	 */
	function goods_sort()
	{
		$goods_id = IFilter::act(IReq::get('id'));
		$sort = IFilter::act(IReq::get('sort'));
		$flag = 0;
		if($goods_id)
		{
			$tb_goods = new IModel('goods');
			$goods_info = $tb_goods->getObj('id='.$goods_id);
			if(count($goods_info)>0)
			{
				if($goods_info['sort']!=$sort)
				{
					$tb_goods->setData(array('sort'=>$sort));
					if($tb_goods->update('id='.$goods_id))
					{
						$flag = 1;
					}
				}
			}
		}
		echo $flag;
	}
 

	/**
	 * @brief 商品添加
	 */
	function goods_add()
	{
		//加载分类
		$tb_category = new IModel('category');
		$goods_class = new goods_class();
		$this->data['category'] = $goods_class->sortdata($tb_category->query(false,'*','sort','asc'),0,' &nbsp;&nbsp;&nbsp;&nbsp; ');
		$this->data['admin_name'] = $this->admin['admin_name'];
		$this->data['admin_pwd']  = $this->admin['admin_pwd'];

		//加载会员级别
		$tb_user_group = new IModel('user_group');
		$info = $tb_user_group->query();
		$ids = '';
		if(count($info)>0){
			foreach ($info as $value)
			{
				$ids .= $value['id'].',';
			}
			$ids = substr($ids,0,-1);
		}

		$this->ids = $ids;
		$this->setRenderData($this->data);
		$this->redirect('goods_add');
	}

	/**
	 * @breif 后台添加为每一件商品添加会员价
	 * */
	function member_price()
	{
		$date = array();
		$this->layout = '';
		$num = IFilter::act(IReq::get('num'));
		$sell_price = IFilter::act(IReq::get('sell_price'),'float');
		if(empty($sell_price))
		{
			$sell_price=0;
		}
		$date['num'] = $num;
		$date['sell_price'] = $sell_price;
		$this->setRenderData($date);
		$this->redirect('member_price');
	}
	/**
	 * @breif 后台修改商品修改会员价
	 * */
	function member_price_edit()
	{
		$date = array();
		$this->layout = '';
		$product_id = IFilter::act(IReq::get('product_id'));
		$sell_price = IFilter::act(IReq::get('sell_price'),'float');
		$goods_id = IFilter::act(IReq::get('goods_id'));
		if(empty($sell_price))
		{
			$sell_price=0;
		}
		if($product_id=='g')
		{
			$product_id = 0;
		}
		$date['product_id'] = $product_id;
		$date['sell_price'] = $sell_price;
		$date['goods_id'] = $goods_id;
		$this->setRenderData($date);
		$this->redirect('member_price_edit');
	}
	/**
	 * @brief 修改商品
	 */
	function goods_edit()
	{
		$goods_id = IFilter::act(IReq::get('gid'),'int');
		//编辑商品 读取商品信息
		$data = array();
		if(!empty($goods_id))
		{
			$obj_goods = new IModel('goods');
			$goods_info = $obj_goods->getObj('id='.$goods_id);
			if(count($goods_info)>0)
			{
				$type = array('gid'=>$goods_id,'admin_name'=>$this->admin['admin_name'],'admin_pwd'=>$this->admin['admin_pwd']);
				$goods = new goods_class();
				$data = $goods->edit($type,$goods_info);
				$this->setRenderData($data);
				$this->redirect('goods_edit');
			}
			else
			{
				//没有找到相关记录
				$this->goods_list();
				Util::showMessage("没有找到相关商品！");
				return;
			}
		}
		if(count($data)==0)
		{
			$this->goods_list();
		}

	}

	/**
	 * @brief 保存商品信息
	 */
	function goods_save()
	{
		//获得post的数据
		$goods_name = IFilter::act(IReq::get('goods_name'));
		$goods_category = IReq::get('goods_category');
		$goods_brand = IFilter::act(IReq::get('goods_brand'),'int');
		$goods_status = IFilter::act(IReq::get('goods_status'),'int');

		$goods_notes = IFilter::act(IReq::get('goods_notes'));
		$goods_from = IFilter::act(IReq::get('goods_from'));
		$goods_sellernick = IFilter::act(IReq::get('goods_sellernick'));;
		$goods_commission = IFilter::act(IReq::get('goods_commission'),'float');
		$goods_url = IFilter::act(IReq::get('goods_url'));
		$goods_img = IFilter::act(IReq::get('goods_img'));
		$list_img = IFilter::act(IReq::get('list_img'));
		$show_img = IFilter::act(IReq::get('small_img'));

		$sell_price = IFilter::act(IReq::get('sell_price'),'float');
		$market_price = IFilter::act(IReq::get('market_price'),'float');
		$cost_price = IFilter::act(IReq::get('cost_price'),'float');
		$store_nums = IFilter::act(IReq::get('store_nums'),'int');
		$weight = IFilter::act(IReq::get('weight'),'float');
		$store_unit = IFilter::act(IReq::get('store_unit'));
		$content = IFilter::act(IReq::get('content'),'text');
		$seo_keywords = IFilter::act(IReq::get('seo_keywords'));
		$seo_description = IFilter::act(IReq::get('seo_description'));
		$point = IFilter::act(IReq::get('point'),'int');
		$exp = IFilter::act(IReq::get('exp'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');
		$focus_photo = IFilter::act(IReq::get('focus_photo'));
		$goods_no = IFilter::act(IReq::get('goods_no'));

		$keywords_for_search = IFilter::act(IReq::get('keywords_for_search'));
		//判断货号如果存在则不能添加
		if($goods_no)
		{
			$tb_good = new IModel('goods');
			$good_info = $tb_good->getObj("goods_no='".$goods_no."'");
			if(count($good_info)>0)
			{
				//加载分类
				$tb_category = new IModel('category');
				$goods_class = new goods_class();
				$this->data['category'] = $goods_class->sortdata($tb_category->query(false,'*','sort','asc'),0,' &nbsp;&nbsp; ');
				$this->data['admin_name'] = $this->admin['admin_name'];
				$this->data['admin_pwd']  = $this->admin['admin_pwd'];

				//加载会员级别
				$tb_user_group = new IModel('user_group');
				$info = $tb_user_group->query();
				$ids = '';
				if(count($info)>0){
					foreach ($info as $value)
					{
						$ids .= $value['id'].',';
					}
					$ids = substr($ids,0,-1);
				}
				$this->ids = $ids;
				$this->setRenderData($this->data);
				$this->redirect('goods_add',false);
				Util::showMessage('您输入的商品货号已存在，请重新输入!');
			}
		}

		//大图片
		$show_img = $focus_photo;
		$list_img = $focus_photo;
		if($focus_photo)
		{
			$foot = substr($focus_photo,strpos($focus_photo,'.'));//图片扩展名
			$head = substr($focus_photo,0,strpos($focus_photo,'.'));

			//获得配置文件中的数据
			$config = new Config("site_config");
			$config_info = $config->getInfo();
			$list_thumb_width  = isset($config_info['list_thumb_width'])  ? $config_info['list_thumb_width']  : 175;
	 		$list_thumb_height = isset($config_info['list_thumb_height']) ? $config_info['list_thumb_height'] : 175;
	 		$show_thumb_width  = isset($config_info['show_thumb_width'])  ? $config_info['show_thumb_width']  : 85;
			$show_thumb_height = isset($config_info['show_thumb_height']) ? $config_info['show_thumb_height'] : 85;
			//list
		 	$list_img = $head.'_'.$list_thumb_width.'_'.$list_thumb_height.$foot;
		 	//show
		 	$show_img = $head.'_'.$show_thumb_width.'_'.$show_thumb_height.$foot;
		}elseif($goods_img){
			$focus_photo = $goods_img;
		}


		/*goods表操作*/
		$tb_goods = new IModel('goods');
		$tb_goods ->setData(array(
			'name' =>$goods_name,
			'notes'=>$goods_notes,
			'goods_no'=>$goods_no,
			'sell_price' =>$sell_price,
			'market_price' => $market_price,
			'cost_price' => $cost_price,
			'store_nums' =>$store_nums,
			'brand_id' =>$goods_brand,
			'content'=>$content,
			'is_del' =>$goods_status,
			'from' =>$goods_from,
			'sellernick' =>$goods_sellernick,
			'commission' =>$goods_commission,
			'url' =>$goods_url,
			'create_time' =>date('Y-m-d H:i:s'),
			'keywords'=>$seo_keywords,
			'description' =>$seo_description,
			'weight'=>$weight,
			'unit' =>$store_unit,
			'sort' => $sort,
			'visit'=>0,
			'favorite'=>0,
			'point' =>$point,
			'exp' => $exp,
			'small_img'=>$show_img,
			'img'=>$focus_photo,
			'list_img'=>$list_img
		));
		$goods_id = $tb_goods->add();
		
		if(!$goods_no)
		{
			//如用户没有输入商品货号，则默认货号
			$goods_no = Block::goods_no($goods_id);
			$tb_goods->setData(array('goods_no'=>$goods_no));
			$tb_goods->update('id='.$goods_id);
		}
		//商品扩展分类
		$tb_category = new IModel('category_extend');
		if ($goods_category)
		{
			$tb_category->setData(array(
				'goods_id'=>$goods_id,
				'category_id'=>$goods_category 
			));
			$tb_category->add();
		}

		//标签关键词
		$keywords_for_search = trim($keywords_for_search,",");
		if($keywords_for_search)
		{
			$keywords_for_search_array = array();
			foreach( explode(",",$keywords_for_search ) as $value)
			{
				if(IString::getStrLen($value) <= 15)
				{
					keywords::add($value , 0);
					$keywords_for_search_array[] = $value;
				}
			}

			if($keywords_for_search_array)
			{
				$data=array('goods_id'=>$goods_id,'keywords'=>join(',',$keywords_for_search_array));
				$obj_goods_keywords = new IModel("goods_keywords");
				$obj_goods_keywords->setData($data);
				$obj_goods_keywords->add();
			}
		}

		/*commend_goods表操作*/
		$goods_commend = IReq::get('goods_commend');
		$tb_commend = new IModel('commend_goods');
		if(!empty($goods_commend))
		{
			if(is_array($goods_commend))
			{
				for ($i=0;$i<count($goods_commend);$i++)
				{
					$tb_commend->setData(array(
						'commend_id'=>$goods_commend[$i],
						'goods_id'=>$goods_id
					));
					$tb_commend->add();
				}
			}
			else
			{
					$tb_commend->setData(array(
						'commend_id'=>$goods_commend,
						'goods_id'=>$goods_id
					));
					$tb_commend->add();
			}
		}
		/*goods_photo_relation表操作*/
		$photo_name = IReq::get('photo_name');
		if($photo_name)
		{
			$photo_name = rtrim($photo_name,',');
			$arr = explode(',',$photo_name);
			if(count($arr)>0)
			{
				$tb_goods_relation = new IModel('goods_photo_relation');
				foreach ($arr as $value)
				{
					$tb_goods_relation->setData(array(
						'goods_id'=>$goods_id,
						'photo_id' =>md5_file($value)
					));
					$tb_goods_relation->add();
				}
			}
		}
		//获得商品的会员价格
		$member_ids = IReq::get('member_ids');
		$tb_group_price = new IModel('group_price');

		$brr = explode(',',$member_ids);
		foreach ($brr as $value)
		{
			$price = IReq::get('memg'.$value);
			if(!empty($price)){
				$tb_group_price->setData(array(
					'goods_id'=>$goods_id,
					'products_id'=>0,
					'group_id'=>$value,
					'price'=>$price
				));
				$tb_group_price->add();
			}
		}
		

		$this->redirect("goods_list");
	}
	/**
	 * @brief 保存修改商品信息
	 */
	function goods_update()
	{
		//获得post的数据
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$goods_name = IFilter::act(IReq::get('goods_name'));
		$goods_category = IReq::get('goods_category');
		$goods_model = IFilter::act(IReq::get('goods_model'),'int');
		$goods_brand = IFilter::act(IReq::get('goods_brand'),'int');
		$goods_status = IFilter::act(IReq::get('goods_status'),'int');

		$goods_notes = IFilter::act(IReq::get('goods_notes'));
		$goods_from = IFilter::act(IReq::get('goods_from'));
		$goods_sellernick = IFilter::act(IReq::get('goods_sellernick'));;
		$goods_commission = IFilter::act(IReq::get('goods_commission'),'float');
		$goods_url = IFilter::act(IReq::get('goods_url'));
		$goods_img = IFilter::act(IReq::get('goods_img'));
		$list_img = IFilter::act(IReq::get('list_img'));
		$show_img = IFilter::act(IReq::get('small_img'));

		$sell_price = IFilter::act(IReq::get('sell_price'),'float');
		$market_price = IFilter::act(IReq::get('market_price'),'float');
		$cost_price = IFilter::act(IReq::get('cost_price'),'float');
		$store_nums = IFilter::act(IReq::get('store_nums'),'int');
		$weight = IFilter::act(IReq::get('weight'),'float');
		$store_unit = IFilter::act(IReq::get('store_unit'));
		$content = IFilter::act(IReq::get('content'),'text');
		$seo_keywords = IReq::get('seo_keywords');
		$seo_description = IReq::get('seo_description');
		$point = IFilter::act(IReq::get('point'),'int');
		$exp = IFilter::act(IReq::get('exp'),'int');
		$sort = IFilter::act(IReq::get('sort'));
		$focus_photo = IFilter::act(IReq::get('focus_photo'));
		$goods_no = IFilter::act(IReq::get('goods_no'));
		$keywords_for_search = IFilter::act(IReq::get('keywords_for_search'));

		$tb_goods = new IModel('goods');
		if(!$goods_no)
		{
			//如用户没有输入商品货号，则默认货号
			$goods_no = Block::goods_no($goods_id);
		}
		else
		{
			$goods_info = $tb_goods->query("goods_no='".$goods_no."'");
			$flag = 2;
			if(count($goods_info)>0)
			{
				if(count($goods_info)==1)
				{
					if($goods_info[0]['id']!=$goods_id)
					{
						$flag = 1;
					}
				}
				else
				{
					$flag = 1;
				}
			}
			if($flag==1)
			{
				$type = array('gid'=>$goods_id,'admin_name'=>$this->admin['admin_name'],'admin_pwd'=>$this->admin['admin_pwd']);
				$goods_in_fo = $tb_goods->getObj('id='.$goods_id);
				$goods = new goods_class();
				$data = $goods->edit($type,$goods_in_fo);
				$this->setRenderData($data);
				$this->redirect('goods_edit',false);
				Util::showMessage("您输入的货号已存在！");
			}
		}
		//标签关键词
		$keywords_for_search = trim($keywords_for_search,",");
		if($keywords_for_search)
		{
			$keywords_for_search_array = array();
			foreach( explode(",",$keywords_for_search ) as $value)
			{
				if(IString::getStrLen($value) <= 15)
				{
					keywords::add($value , 0);
					$keywords_for_search_array[] = $value;
				}
			}

			if($keywords_for_search_array)
			{
				$data=array('goods_id'=>$goods_id,'keywords'=>join(',',$keywords_for_search_array));
				$obj_goods_keywords = new IModel("goods_keywords");
				$obj_goods_keywords->setData($data);
				if($obj_goods_keywords->getObj("goods_id={$goods_id}"))
				{
					$obj_goods_keywords->update("goods_id={$goods_id}");
				}
				else
				{
					$obj_goods_keywords->add();
				}
			}
		}

		//大图片
		$show_img = $focus_photo;
		$list_img = $focus_photo;
		if($focus_photo)
		{
			$foot = substr($focus_photo,strpos($focus_photo,'.'));//图片扩展名
			$head = substr($focus_photo,0,strpos($focus_photo,'.'));

			//获得配置文件中的数据
			$config = new Config("site_config");
			$config_info = $config->getInfo();
			$list_thumb_width  = isset($config_info['list_thumb_width'])  ? $config_info['list_thumb_width']  : 175;
	 		$list_thumb_height = isset($config_info['list_thumb_height']) ? $config_info['list_thumb_height'] : 175;
	 		$show_thumb_width  = isset($config_info['show_thumb_width'])  ? $config_info['show_thumb_width']  : 85;
			$show_thumb_height = isset($config_info['show_thumb_height']) ? $config_info['show_thumb_height'] : 85;
		 	//list
		 	$list_img = $head.'_'.$list_thumb_width.'_'.$list_thumb_height.$foot;
		 	//show
		 	$show_img = $head.'_'.$show_thumb_width.'_'.$show_thumb_height.$foot;
		}elseif($goods_img){
			$focus_photo = $goods_img;
		}
		//规格
		$spec_va = IReq::get('spec_va');
		$spec = array();
		$spec_array = array();
		if($spec_va)
		{
			$arr = explode(';',$spec_va);
			$i = 0;
			foreach ($arr as $value)
			{
				if($value)
				{
					$brr = explode('|',$value);
					$j=0;
					foreach ($brr as $va)
					{
						$crr = explode(',',$va);
						$spec[$i][$j]['id'] = $crr[1];
						$spec[$i][$j]['name'] = $crr[2];
						$spec[$i][$j]['type'] = $crr[3];

						//商品规格类型
						$spec_array[$j]['id'] = $crr[1];
						if(!isset($spec_array[$j]['value']))
						{
							$spec_array[$j]['value'] = $crr[2].',';
						}
						else
						{
							if(!strpos(',,'.$spec_array[$j]['value'],','.$crr[2].','))
							{
								$spec_array[$j]['value'] .=$crr[2].',';
							}
						}
						$spec_array[$j]['type'] = $crr[3];
						$spec_array[$j]['name'] = $crr[4];

						$j++;
					}
					$i++;
				}
			}
		}
		/*goods表操作*/
		$tb_goods ->setData(array(
			'name' =>$goods_name,
			'notes'=>$goods_notes,
			'goods_no'=>$goods_no,
			'sell_price' =>$sell_price,
			'market_price' => $market_price,
			'cost_price' => $cost_price,
			'store_nums' =>$store_nums,
			'brand_id' =>$goods_brand,
			'is_del' =>$goods_status,
			'from' =>$goods_from,
			'sellernick' =>$goods_sellernick,
			'commission' =>$goods_commission,
			'url' =>$goods_url,
			'content'=>$content,
			'keywords'=>$seo_keywords,
			'description' =>$seo_description,
			'weight'=>$weight,
			'unit' =>$store_unit,
			'point' =>$point,
			'exp' =>$exp,
			'sort' => $sort,
			'small_img'=>$show_img,
			'img'=>$focus_photo,
			'list_img'=>$list_img
		));
		$tb_goods->update('id='.$goods_id);
		//商品扩展分类
		$tb_category = new IModel('category_extend');
		$tb_category->del('goods_id='.$goods_id);
		if ($goods_category)
		{
			$tb_category->setData(array(
				'goods_id'=>$goods_id,
				'category_id'=>$goods_category 
			));
			$tb_category->add();
		}
		
		/*commend_goods表操作*/
		$goods_commend = IReq::get('goods_commend');
		$tb_commend = new IModel('commend_goods');
		$tb_commend->del('goods_id='.$goods_id);
		if(!empty($goods_commend))
		{
			if(is_array($goods_commend))
			{
				for ($i=0;$i<count($goods_commend);$i++)
				{
					$tb_commend->setData(array(
						'commend_id'=>$goods_commend[$i],
						'goods_id'=>$goods_id
					));
					$tb_commend->add();
				}
			}
			else
			{
					$tb_commend->setData(array(
						'commend_id'=>$goods_commend,
						'goods_id'=>$goods_id
					));
					$tb_commend->add();
			}
		}
		/*goods_photo_relation表操作*/
		$photo_name = IReq::get('photo_name');
		$tb_goods_relation = new IModel('goods_photo_relation');
		$tb_goods_relation->del('goods_id='.$goods_id);
		if($photo_name)
		{
			$photo_name = substr($photo_name,0,-1);
			$arr = explode(',',$photo_name);
			if(count($arr)>0)
			{
				foreach ($arr as $value)
				{
					//当图片存在的时候保存
					if(file_exists($value))
					{
						$tb_goods_relation->setData(array(
							'goods_id'=>$goods_id,
							'photo_id' =>md5_file($value)
						));
						$tb_goods_relation->add();
					}
				}
			}
		}
		/*products表以及group_price的操作*/
		$member_ids = IFilter::act(IReq::get('member_ids'));
		$group_id = IFilter::act(IReq::get('group_id'));
		$products_id = IFilter::act(IReq::get('products_id'));
		//先对products表操作,先修改，再删除没有了的pro
		$tb_products = new Imodel('products');
		$tb_group_ob = new Imodel('group_price');
		if($group_id)
		{
			$tb_group_ob->del('id in ('.$group_id.')');
		}
		$store_nums = 0;//商品数量
		if($spec_va)
		{
			$sell_price_array = array();//所有货品的销售价格
			$market_price_array = array();//所有货品的市场价格
			$cost_price_array = array();//所有货品的成本价格
			$weight_array = array();//所有货品的重量
			$arr = explode(';',$spec_va);
			$i=0;
			foreach ($arr as $value)
			{
				if($value)
				{
					$brr = explode('|',$value);
					$j=0;
					$ids = array();
					$spec_md5 = '';
					$pro_id = '';
					$new_pro = '';
					foreach ($brr as $va)
					{
						$crr = explode(',',$va);
						$pro_id = $crr[0];
						$new_pro = $pro_id;

						//判断商品是否为新添加的，如果是则pro_id以a开头
						if(stristr($pro_id,'a')!='')
						{
							$pro_id = substr($pro_id,1);
						}
						$ids[$j]['id'] = $crr[1];
						$ids[$j]['value'] = $crr[2];

						$spec_md5 .=md5($ids[$j]['value']).',';
						$j++;
					}
					$specTemp = explode(',',trim($spec_md5,','));
					sort($specTemp);

					$spec_md5 = md5(serialize($specTemp));
					$store_nums += IReq::get('store_nums'.$pro_id);
					$tb_products->setData(array(
						'goods_id'=>$goods_id,
						'products_no'=>IReq::get('goods_no'.$pro_id)?IReq::get('goods_no'.$pro_id):$goods_no.'-'.($i+1),
						'spec_array'=>serialize($ids),
						'market_price'=>IReq::get('market_price'.$pro_id)?IReq::get('market_price'.$pro_id):$market_price,
						'sell_price'=>IReq::get('sell_price'.$pro_id)?IReq::get('sell_price'.$pro_id):$sell_price,
						'store_nums'=>IReq::get('store_nums'.$pro_id)?IReq::get('store_nums'.$pro_id):$store_nums,
						'cost_price'=>IReq::get('cost_price'.$pro_id)?IReq::get('cost_price'.$pro_id):$cost_price,
						'weight'=>IReq::get('weight'.$pro_id)?IReq::get('weight'.$pro_id):$weight,
						'spec_md5' =>$spec_md5
					));
					//获得所有的货品的销售价格、市场价格、成本价格、货品的重量
					$sell_price_array[] = IReq::get('sell_price'.$pro_id)?IReq::get('sell_price'.$pro_id):$sell_price;
					$market_price_array[] = IReq::get('market_price'.$pro_id)?IReq::get('market_price'.$pro_id):$market_price;
					$cost_price_array[] = IReq::get('cost_price'.$pro_id)?IReq::get('cost_price'.$pro_id):$cost_price;
					$weight_array[] = IReq::get('weight'.$pro_id)?IReq::get('weight'.$pro_id):$weight;
					$mem_array = explode(',',$member_ids);
					if(strpos('|'.$new_pro,'a')>0)
					{
						$pr_id = $tb_products->add();
						foreach ($mem_array as $cc)
						{
							$gro_price = IFilter::act(IReq::get('mem_0_'.$new_pro.'_'.$cc),'int');
							if($gro_price>0 && $pr_id!=0)
							{
								$tb_group_ob->setData(array(
									'goods_id'=>$goods_id,
									'products_id'=>$pr_id,
									'group_id'=>$cc,
									'price'=>$gro_price
								));
								$tb_group_ob->add();
							}
						}
					}
					else
					{
						$tb_products->update('id='.$pro_id);
						$group_arr = explode(',',$group_id.',0');
						if($group_arr)
						{
							foreach ($group_arr as $va)
							{
								foreach ($mem_array as $cc)
								{
									$gro_price = IFilter::act(IReq::get('mem_'.$va.'_'.$pro_id.'_'.$cc),'int');
									if($gro_price>0)
									{
										$tb_group_ob->setData(array(
											'goods_id'=>$goods_id,
											'products_id'=>$pro_id,
											'group_id'=>$cc,
											'price'=>$gro_price
										));
										$tb_group_ob->add();
									}
								}
							}
						}
						else
						{
							foreach ($mem_array as $cc)
							{
								$gro_price = IFilter::act(IReq::get('mem_0_'.$pro_id.'_'.$cc),'int');
								if($gro_price>0)
								{
									$tb_group_ob->setData(array(
										'goods_id'=>$goods_id,
										'products_id'=>$pro_id,
										'group_id'=>$cc,
										'price'=>$gro_price
									));
									$tb_group_ob->add();
								}
							}
						}
					}
				}
				$i++;
			}
			//如果商品的价格为空，则将货品的销售价格中最低的赋予
			$addition = array('store_nums'=>$store_nums);
			if(!empty($sell_price_array))
			{
				$addition['sell_price'] = min($sell_price_array);
			}
			if(!empty($market_price_array))
			{
				$addition['market_price'] = min($market_price_array);
			}
			if(!empty($cost_price_array))
			{
				$addition['cost_price'] = min($cost_price_array);
			}
			if(!empty($weight_array))
			{
				$addition['weight'] = min($weight_array);
			}
			$tb_goods->setData($addition);
			//如果有products数据，则将products中的货品数量全部相加并送入goods表
			$tb_goods->update('id='.$goods_id);
		}
		$mem_array = explode(',',$member_ids);
		$group_arr = explode(',',$group_id.',0');
		if($group_arr)
		{
			foreach ($group_arr as $va)
			{
				foreach ($mem_array as $cc)
				{
					$gro_price = IFilter::act(IReq::get('mem_'.$va.'_0_'.$cc),'int');
					if($gro_price>0)
					{
						$tb_group_ob->setData(array(
							'goods_id'=>$goods_id,
							'products_id'=>0,
							'group_id'=>$cc,
							'price'=>$gro_price
						));
						$tb_group_ob->add();
					}
				}
			}
		}
		//获得删除的products_id
		$del_products_id = IFilter::act(IReq::get('del_products_id'));
		if($del_products_id)
		{
			$del_products_id = substr($del_products_id,0,-1);
			$info = explode(',',$del_products_id);
			foreach ($info as $value) {
				if(strpos('|'.$value,'a')==false)
				{
					$tb_products->del('id='.$value);
				}
			}
		}

		$this->redirect("goods_list");
	}
	/**
	 * @brief 删除商品
	 */
	function goods_del()
	{
		//post数据
	    $id = IFilter::act(IReq::get('id'));
	    //生成goods对象
	    $tb_goods = new IModel('goods');
	    $tb_goods->setData(array('is_del'=>1));
	    if(!empty($id))
		{
			$tb_goods->update(Util::getWhere($id));
		}
		else
		{
			Util::showMessage('请选择要删除的数据');
		}
		$this->redirect("goods_list");
	}
	/**
	 * @brief 商品上下架
	 */
	function goods_stats()
	{
		//post数据
	    $id = IFilter::act(IReq::get('id'));
	    $type = IFilter::act(IReq::get('type'));
	    //生成goods对象
	    $tb_goods = new IModel('goods');
	    $arr = array();
	    if($type=='up')
	    {
	    	$arr['is_del'] = '0';
	    }
	    else
	    {
	    	$arr['is_del'] = '2';
	    }
	    $tb_goods->setData($arr);
	    if(!empty($id))
		{
			$tb_goods->update(Util::getWhere($id));
		}
		else
		{
			if($type=='up')
			{
				Util::showMessage('请选择要上架的数据');
			}
			else
			{
				Util::showMessage('请选择要下架的数据');
			}
		}
		$this->redirect("goods_list");
	}
	/**
	 * @brief 商品彻底删除
	 * */
	function goods_recycle_del()
	{
		//post数据
	    $id = IFilter::act(IReq::get('id'));
	    //生成goods对象
	    $goods = new goods_class();
	    if(!empty($id))
		{
			if(is_array($id) && isset($id[0]) && $id[0]!='')
			{
				for ($i=0;$i<count($id);$i++)
				{
					$goods->del($id[$i]);
				}
			}
			else
			{
				$goods->del($id);
			}
		}
		$this->redirect("goods_recycle_list");
	}
	/**
	 * @brief 商品还原
	 * */
	function goods_recycle_restore()
	{
		//post数据
	    $id = IFilter::act(IReq::get('id'));
	    //生成goods对象
	    $tb_goods = new IModel('goods');
	    $tb_goods->setData(array('is_del'=>0));
	    if(!empty($id))
		{
			$tb_goods->update(Util::getWhere($id));
		}
		else
		{
			Util::showMessage('请选择要删除的数据');
		}
		$this->redirect("goods_recycle_list");
	}
	/**
	 * @brief 商品回收站
	 * */
	function goods_recycle_list()
	{
		$search = IReq::get('search');
		$keywords = IReq::get('keywords');
		$where = ' 1 ';
		$left_join = '';
		if($search && $keywords)
		{
			if($search=='c.name')
			{
				$left_join = " left join category_extend as ce on ce.goods_id=goods.id left join category as ca on ce.category_id=ca.id";
				$where .= " and ca.name like '%{$keywords}%' ";
			}
			else
			{
				$where .= " and $search like '%{$keywords}%' ";
			}
		}
		$this->data['search'] = $search;
		$this->data['keywords'] = $keywords;
		//筛选
		$category_id = IReq::get('category_id');
		$added = IReq::get('added');
		$store_nums = IReq::get('store_nums');
		$commend = IReq::get('commend');
		$this->data['category_id'] = $category_id;
		$this->data['added'] = $added;
		$this->data['store_nums'] = $store_nums;
		$this->data['commend'] = $commend;
		if($added!='')
		{
			if($added=='0')
			{
				$where .= "and is_del=0 ";
			}
			else
			{
				$where .= "and is_del=2 ";
			}
		}
		if($store_nums)
		{
			if($store_nums=='1')
			{
				$where .= " and store_nums<=0 ";
			}
			if($store_nums=='10')
			{
				$where .= " and store_nums>0 and store_nums<10 ";
			}
			if($store_nums=='100')
			{
				$where .= " and store_nums>=10 and store_nums<=100 ";
			}
			if($store_nums=='101')
			{
				$where .= " and store_nums>100 ";
			}
		}

		if($category_id)
		{
			$left_join .= " left join category_extend as ce on ce.goods_id=goods.id ";
			$where .= " and ce.category_id = ".$category_id." ";
		}
		if($commend)
		{
			$left_join .= ' left join commend_goods as cg on cg.goods_id = goods.id ';
			$where .= ' and cg.commend_id='.$commend.'';
		}
		$this->data['where'] = $where;
		$this->data['left_join'] = $left_join;
		$this->setRenderData($this->data);
		$this->redirect("goods_recycle_list");
	}
	/**
	 * @brief 商品列表
	 */
	function goods_list()
	{
		//商品搜索
		$search = IReq::get('search');
		$keywords = IReq::get('keywords');
		$where = ' 1 ';
		$left_join = '';
		if($search && $keywords)
		{
			if($search=='c.name')
			{
				$left_join = " left join category_extend as ce on ce.goods_id=goods.id left join category as ca on ce.category_id=ca.id";
				$where .= " and ca.name like '%{$keywords}%' ";
			}
			else
			{
				$where .= " and $search like '%{$keywords}%' ";
			}
		}
		$this->data['search'] = $search;
		$this->data['keywords'] = $keywords;
		//筛选
		$category_id = IReq::get('category_id');
		$added = IReq::get('added');
		$store_nums = IReq::get('store_nums');
		$commend = IReq::get('commend');
		$this->data['category_id'] = $category_id;
		$this->data['added'] = $added;
		$this->data['store_nums'] = $store_nums;
		$this->data['commend'] = $commend;
		if($added!='')
		{
			if($added=='0')
			{
				$where .= "and is_del=0 ";
			}
			else
			{
				$where .= "and is_del=2 ";
			}
		}
		if($store_nums)
		{
			if($store_nums=='1')
			{
				$where .= " and store_nums<=0 ";
			}
			if($store_nums=='10')
			{
				$where .= " and store_nums>0 and store_nums<10 ";
			}
			if($store_nums=='100')
			{
				$where .= " and store_nums>=10 and store_nums<=100 ";
			}
			if($store_nums=='101')
			{
				$where .= " and store_nums>100 ";
			}
		}
		if($category_id)
		{
			$left_join .= " left join category_extend as ce on ce.goods_id=goods.id ";
			$category = substr($category_id.','.Block::getCategroy($category_id),0,-1);
			$where .= " and ce.category_id in (".$category.")";
		}

		if($commend)
		{
			$left_join .= ' left join commend_goods as cg on cg.goods_id = goods.id ';
			$where .= ' and cg.commend_id='.$commend.'';
		}
		$this->data['where'] = $where;
		$this->data['left_join'] = $left_join;
		$this->setRenderData($this->data);
		$this->redirect("goods_list");
	}
	/**
	 * @brief 商品分类添加、修改
	 */
	function category_edit()
	{
		$category_id = (int)IReq::get('cid');
		$parent_id = (int)IReq::get('pid');
		$this->data['category']['parent_id'] = $parent_id;
		//编辑商品分类 读取商品分类信息
		if($category_id)
		{
			$obj_category = new IModel('category');
			$category_info = $obj_category->query('id='.$category_id);
			if(is_array($category_info) && $info=$category_info[0])
			{
				$this->data['category_id'] = $category_id;
				$this->data['category'] = array(
					'id'		=>	$info['id'],
					'name'		=>	$info['name'],
					'parent_id'	=>	$info['parent_id'],
					'sort'		=>	$info['sort'],
					'visibility'=>	$info['visibility'],
					'keywords'	=>	$info['keywords'],
					'descript'	=>	$info['descript']
				);
			}
			else
			{
				$this->category_list();
				Util::showMessage("没有找到相关商品分类！");
				return;
			}
		}

		//加载分类
		$tb_category = new IModel('category');
		$goods = new goods_class();
		$this->data['all_category'] = $goods->sortdata($tb_category->query(false,'*','sort','asc'),0,' &nbsp;&nbsp; ');
		$this->setRenderData($this->data);
		$this->redirect('category_edit');
	}

	/**
	 * @brief 保存商品分类
	 */
	function category_save()
	{
		//获得post值
		$category_id = IFilter::act(IReq::get('category_id'),'int');
		$name = IFilter::act(IReq::get('name'));
		$parent_id = IFilter::act(IReq::get('parent_id'),'int');
		$visibility = IFilter::act(IReq::get('visibility'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');
		$title = IFilter::act(IReq::get('title'));
		$keywords = IFilter::act(IReq::get('keywords'));
		$descript = IFilter::act(IReq::get('descript'));

		$tb_category = new IModel('category');
		$category_info = array(
			'name'=>$name,
			'parent_id'=>$parent_id,
			'sort'=>$sort,
			'visibility'=>$visibility,
			'keywords'=>$keywords,
			'descript'=>$descript,
			'title'=>$title
		);
		$tb_category->setData($category_info);
		if($category_id)									//保存修改分类信息
		{
			$where = "id=".$category_id;
			$tb_category->update($where);
		}
		else												//添加新商品分类
		{
			$tb_category->add();
		}

		//更新缓存
		$cacheObj = new ICache('file');
		$cacheObj->del('goodsCategory');

		$this->category_list();
	}

	/**
	 * @brief 删除商品分类
	 */
	function category_del()
	{
		$category_id = IFilter::act(IReq::get('cid'),'int');
		if($category_id)
		{
			$tb_category = new IModel('category');
			$catRow      = $tb_category->getObj('parent_id = '.$category_id);

			//要删除的分类下还有子节点
			if(!empty($catRow))
			{
				$this->category_list();
				Util::showMessage('无法删除此分类，此分类下还有子分类');
				exit;
			}

			$tb_category_extend  = new IModel('category_extend');
			$cate_ext = $tb_category_extend->getObj('category_id = '.$category_id);

			//要删除的分类下还有商品
			if(!empty($cate_ext))
			{
				$this->category_list();
				Util::showMessage('此分类下还有商品,请先删除商品！');
				exit;
			}

			if($tb_category->del('id = '.$category_id))
			{
				//更新缓存
				$cacheObj = new ICache('file');
				$cacheObj->del('goodsCategory');

				$this->category_list();
			}
			else
			{
				$this->category_list();
				$msg = "没有找到相关分类记录！";
				Util::showMessage($msg);
			}
		}
		else
		{
			$this->category_list();
			$msg = "没有找到相关分类记录！";
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 商品分类列表
	 */
	function category_list()
	{

		//加载分类
		$tb_category = new IModel('category');
		$goods = new goods_class();
		$this->data['category'] = $goods->sortdata($tb_category->query(false,'*','sort','asc'));
		$this->setRenderData($this->data);
		$this->redirect('category_list',false);
	}


	/**
	 * @brief 分类排序
	 */
	function category_sort()
	{
		$category_id = IFilter::act(IReq::get('id'));
		$sort = IFilter::act(IReq::get('sort'));

		//更新缓存
		$cacheObj = new ICache('file');
		$cacheObj->del('goodsCategory');

		$flag = 0;
		if($category_id)
		{
			$tb_category = new IModel('category');
			$category_info = $tb_category->getObj('id='.$category_id);
			if(count($category_info)>0)
			{
				if($category_info['sort']!=$sort)
				{
					$tb_category->setData(array('sort'=>$sort));
					if($tb_category->update('id='.$category_id))
					{
						$flag = 1;
					}
				}
			}
		}
		echo $flag;
	}
	/**
	 * @brief 品牌分类排序
	 */
	function brand_sort()
	{
		$brand_id = IFilter::act(IReq::get('id'));
		$sort = IFilter::act(IReq::get('sort'));
		$flag = 0;
		if($brand_id)
		{
			$tb_brand = new IModel('brand');
			$brand_info = $tb_brand->getObj('id='.$brand_id);
			if(count($brand_info)>0)
			{
				if($brand_info['sort']!=$sort)
				{
					$tb_brand->setData(array('sort'=>$sort));
					if($tb_brand->update('id='.$brand_id))
					{
						$flag = 1;
					}
				}
			}
		}
		echo $flag;
	}

	function word_seg()
	{
		$badReqReturn = JSON::encode(array('flag'=>-1));
		$title = IReq::get("title");
		//参数不全或者少于等于3个字不分词
		if($title == null || preg_match("!^.{0,3}$!u",trim($title)) )
		{
			echo $badReqReturn.'11';
			die();
		}

		$siteConfig = new Config("site_config");
		$siteConfig = $siteConfig->getInfo();
		$iweb_license_key = isset($siteConfig['iweb_license_key']) ? $siteConfig['iweb_license_key'] : "";
		$data = wordseg::run($title, $iweb_license_key);

		$word = array();
		foreach($data['words'] as $key => $value)
		{
			if(!preg_match("!^.$!u",$value['word']))
			{
				$word[] = $value['word'];
			}
		}
		echo JSON::encode(array('flag'=>1,'data'=>$word));
		die();
	}
	/**
	 * 导出商品csv
	 * */
	function export_csv()
	{
		$id = IReq::get('id');
		$this->id = $id;
		$this->layout = '';
		$this->redirect('export_csv');
	}
	/**
	 * 导出商品csv
	 * */
	function output_csv()
	{
		$id = IReq::get('id');
		$date_format = IReq::get('date_format');
		//淘宝数据
		$tao_arr = array();
		$tao_arr[] = IReq::get('category');
		$tao_arr[] = IReq::get('ems');
		$tao_arr[] = IReq::get('exp');
		$tao_arr[] = IReq::get('post');
		$csvObj = new Csv();
		if($id)
		{
			$good_id = explode(',',$id);
			$csvObj->export($date_format,$good_id,$tao_arr);
		}
		else
		{
			$arr = array();
			$tb_goods = new IModel('goods');
			$goods_info = $tb_goods ->query('','id');
			foreach ($goods_info as $value)
			{
				$arr[] = $value['id'];
			}
			$csvObj->export($date_format,$arr,$tao_arr);
		}
		exit;
	}
	/**
	 * 导入商品csv
	 * */
	function import_csv()
	{
		$this->layout = '';
		$this->redirect('import_csv');
	}
	/**
	 * 导入商品csv
	 * */
	function upload_csv()
	{
		$mark = IReq::get('marked');
		$csvType = IReq::get('date_format');
		//调用文件上传类
		$uploadObj = new IUpload(10240,array('csv'));
		$uploadObj->setDir('upload/'.date('Y/m/d'));
		$photo = $uploadObj->execute();
		if(!isset($photo['attach'][0]['flag']) || $photo['attach'][0]['flag']=='-1')
		{
			echo "<br /><div align='center'>请选择CSV文件</div>";
			exit;
		}
		//上传路径
		$csvfile = $photo['attach'][0]['fileSrc'];

		//创建csv对象
		$csvObj = new Csv();
		$if_sucee = $csvObj->import($csvType,$csvfile,$mark);
		if($if_sucee=='0')
		{
			IFile::unlink($csvfile);//导入成功，删除csv文件
			echo "<br /><div align='center'>商品CSV导入成功</div>";
		}
		else
		{
			IFile::unlink($csvfile);//导入失败，删除csv文件
			echo "<br /><div align='center'>商品CSV导入失败</div>";
		}
	}
}
