<?php
/**
 * @brief 公共模块
 * @class Block
 */
class Block extends IController
{
	public $layout='';

	public function init()
	{
		$checkObj = new CheckRights($this);
		$checkObj->checkUserRights();
	}

 	/**
	 * @brief Ajax获取规格值
	 */
	static function spec_value_list()
	{
		// 获取POST数据
		$spec_id = IFilter::act( IReq::get('id') );

		//初始化spec商品模型规格表类对象
		$specObj = new IModel('spec');
		//根据规格编号 获取规格详细信息
		$spec_value = $specObj->getObj("id = $spec_id",array('value','type','note','name'));
		if($spec_value['value'])
		{
			//返回Josn数据
			$json_data = array('value' => unserialize($spec_value['value']),'type'=>$spec_value['type'],'note' => $spec_value['note'],'name' => $spec_value['name']);
			echo JSON::encode($json_data);
		}
		else
		{
			//返回失败标志
			echo 0;
		}
	}

	/**
	 * @brief Ajax获取规格列表
	 */
	static function ajax_spec_list()
	{
		//初始化spec商品模型规格表类对象
		$specObj = new IModel('spec');
		//根据规格编号 获取规格详细信息
		$spec_list = $specObj->query(false,array('id','name','note'));
		if($spec_list)
		{
			//返回Josn数据
			echo JSON::encode($spec_list);
		}
		else
		{
			//返回失败标志
			echo 0;
		}
	}

	//规格添加页面
	//修改页面
	function spec_edit()
	{
		if($id = IFilter::act( IReq::get('id'),'int') )
		{
			$where = 'id = '.$id;
			$obj = new IModel('spec');
			$dataRow = $obj->getObj($where);
		}
		else
		{
			$dataRow = array(
				'id'   => null,
				'name' => null,
				'type' => null,
				'value'=> null,
				'note' => null,
			);
		}
		$this->setRenderData($dataRow);
		$this->redirect('spec_edit');
	}

	//列出筛选商品
	function goods_list()
	{
		//商品检索条件
		$show_num  = IFilter::act( IReq::get('show_num','post'),'int');
		$keywords  = IFilter::act( IReq::get('keywords','post') );
		$cat_id    = IFilter::act( IReq::get('category_id','post'),'int' );
		$min_price = IFilter::act( IReq::get('min_price','post'),'float' );
		$max_price = IFilter::act( IReq::get('max_price','post'),'float' );

		//查询条件
		$where = 'go.is_del = 0';
		if($cat_id)
		{
			$table_name = 'goods as go,category_extend as ca';
			$where     .= " and ca.category_id = {$cat_id} and go.id = ca.goods_id ";
		}
		else
		{
			$table_name = 'goods as go';
		}

		$where.= $keywords  ? ' and go.name like "%'.$keywords.'%"': '';

		$where.= $min_price ? ' and go.sell_price  >= '.$min_price : '';
		$where.= $max_price ? ' and go.sell_price  <= '.$max_price : '';

		$obj        = new IModel($table_name);
		$this->data = $obj->query($where,'go.id,go.name,go.list_img','go.id','desc',$show_num);
		$this->type = IReq::get('type','get');
		$this->redirect('goods_list');
	}
	//获得商品货号
	static public function goods_no($goods_id)
	{
		//获得配置文件中的数据
		$config = new Config("site_config");
	 	$goods_no_pre = $config->goods_no_pre;
		if(!empty($goods_no_pre))
	 	{
	 		if(strlen($goods_no_pre)>2)
	 		{
	 			$goods_no_pre = substr($goods_no_pre,0,2);
	 		}
	 		else if(strlen($goods_no_pre)==1)
	 		{
	 			$goods_no_pre = $goods_no_pre."0";
	 		}
	 	}
	 	else
	 	{
	 		$goods_no_pre = 'SD';
	 	}
	 	//判断加0的个数
		if((16-2-strlen($goods_id))>0)
		{
			$j = 16-2-strlen($goods_id);
			for ($i = 0; $i < $j; $i++) {
				$goods_no_pre = $goods_no_pre."0";
			}
		}
	 	//组合货号
	 	$goods_no_pre = $goods_no_pre.$goods_id;
	 	return $goods_no_pre;
	}
	//提取已经选定的商品
	static function goods_select()
	{
		$id_str = IReq::get('id_str');
		if(!empty($id_str))
		{
			$id_str = explode(",",$id_str);
			$id_str = Util::intval_value($id_str);
			$id_str = implode(",",$id_str);

			$goodsObj = new IModel('goods');
			$where    = 'id in ('.$id_str.')';
			$data     = $goodsObj->query($where);

			$result = array(
				'isError' => false,
				'data'    => $data,
				'id_str'  => $id_str,
			);
		}
		else
		{
			$result = array(
				'isError' => true,
				'message' => '请选择要关联的商品',
			);
		}
		echo JSON::encode($result);
	}

	/**
	 * @brief 商品添加后图片的链接地址
	 */
	function goods_photo_link()
	{
		$img = IReq::get('img');
		$img = substr($img,1);
		$foot = substr($img,strpos($img,'.'));//图片扩展名
		$head = substr($img,0,strpos($img,'.'));
		//获得配置文件中的数据
		$config = new Config("site_config");
		$config_info = $config->getInfo();

		$list_thumb_width  = isset($config_info['list_thumb_width'])  ? $config_info['list_thumb_width']  : 175;
	 	$list_thumb_height = isset($config_info['list_thumb_height']) ? $config_info['list_thumb_height'] : 175;
	 	$show_thumb_width  = isset($config_info['show_thumb_width'])  ? $config_info['show_thumb_width']  : 85;
		$show_thumb_height = isset($config_info['show_thumb_height']) ? $config_info['show_thumb_height'] : 85;

		$data['img'] = IUrl::creatUrl().$img;
		$data['list_img'] = IUrl::creatUrl().$head.'_'.$list_thumb_width.'_'.$list_thumb_height.$foot;
		$data['small_img'] = IUrl::creatUrl().$head.'_'.$show_thumb_width.'_'.$show_thumb_height.$foot;
		$this->setRenderData($data);
		$this->redirect('goods_photo_link');
	}



    //[公共方法]通过序列化数据查询展示规格 key:规格名称;value:规格值
    static function show_spec($specSerialize)
    {
    	$specValArray = array();
    	$specArray    = unserialize($specSerialize);

    	foreach($specArray as $val)
    	{
    		$specValArray[$val['id']] = $val['value'];
    	}
    	$specIds   = join(',',array_keys($specValArray));
    	$specObj   = new IModel('spec');
    	$where     = 'id in ('.$specIds.')';
    	$specData  = $specObj->query($where,'id,name,type');

    	$spec      = array();

    	foreach($specData as $val)
    	{
    		if($val['type'] == 1)
    		{
    			$spec[$val['name']] = $specValArray[$val['id']];
    		}
    		else
    		{
    			$spec[$val['name']] = '<img src="'.IUrl::creatUrl().$specValArray[$val['id']].'" class="img_border" style="width:15px;height:15px;" />';
    		}
    	}
    	return $spec;
    }

	//商品分类,等级共分为3级
	static function goods_category()
	{
		//获取商品分类缓存
		$cacheObj  = new ICache('file');
		$catResult = $cacheObj->get('goodsCategory');
		if($catResult)
		{
			return $catResult;
		}

		$catResult = array();
		$catObj    = new IModel('category');
		$catFirst  = $catObj->query('parent_id = 0','id,name,parent_id,visibility','sort','asc');
		$catOther  = $catObj->query('parent_id != 0','id,name,parent_id,visibility','sort','asc');

		foreach($catFirst as $first_key => $first)
		{
			foreach($catOther as $other_key => $other_val)
			{
				if($first['id'] == $other_val['parent_id'])
				{
					//拼接二级分类
					$first['second'][$other_key] = $other_val;

					//拼接二级以下所有分类
					$catMore = array();
					self::recursion_goods_category($other_val,$catOther,$catObj,$catMore);
					$first['second'][$other_key]['more'] = $catMore;
				}
			}

			$catResult[] = $first;
		}

		//写入缓存
		$cacheObj->set('goodsCategory',$catResult);
		return $catResult;
	}

	//递归获取分类
	static function recursion_goods_category($data,$catOther,$catObj,&$catMore = '')
	{
		if(!empty($data) && !empty($catOther))
		{
			foreach($catOther as $okey => $oval)
			{
				if($data['id'] == $oval['parent_id'])
				{
					unset($catOther[$okey]);
					$catMore[] = $oval;
					self::recursion_goods_category($oval,$catOther,$catObj,$catMore);
				}
			}
		}
	}

	//根据总分类查找所需分类的树结构
	static function getCatTree($catList,$catId = '')
	{
		if(intval($catId) != 0)
		{
			foreach($catList as $firstKey => $firstVal)
			{
				if($firstVal['id'] == $catId)
				{
					return $catList[$firstKey];
				}
				else
				{
					if(!empty($firstVal['second']))
					{
						foreach($firstVal['second'] as $secondKey => $secondVal)
						{
							if($secondVal['id'] == $catId)
							{
								return $catList[$firstKey];
							}
							else
							{
								if(!empty($secondVal['more']))
								{
									foreach($secondVal['more'] as $moreKey => $moreVal)
									{
										if($moreVal['id'] == $catId)
										{
											return $catList[$firstKey];
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return array();
	}

	//[条件检索url处理]对于query url中已经存在的数据进行删除;没有的参数进行添加
	static function searchUrl($queryKey,$queryVal = '')
	{
		if(is_array($queryKey))
		{
			$concatStr = '';
			$fromStr   = array();
			$toStr     = array();

			foreach($queryKey as $k => $v)
			{
				$urlVal  = IReq::get($v);
				$tempVal = isset($queryVal[$k]) ? $queryVal[$k] : $queryVal;

				if($urlVal === null)
				{
					$concatStr.='&'.$v.'='.$tempVal;
				}
				else
				{
					$fromStr[] = '&'.$v.'='.$urlVal;
					$toStr[]   = '&'.$v.'='.$tempVal;
				}
			}
			return str_replace($fromStr,$toStr,'?'.$_SERVER['QUERY_STRING']).$concatStr;
		}
		else
		{
			/*URL变量 arg[key] 格式支持
			 *由于在 URL get方式传参时系统会把变量 arg[key] 直接判定为数组
			 *所以这里需要对此类参数进行特殊处理;
			 */
			preg_match('|(\w+)\[(\d+)\]|',$queryKey,$match);
			$urlVal = null;

			if(isset($match[2]))
			{
				$urlArray = IReq::get($match[1]);
				if(isset($urlArray[$match[2]]))
				{
					$urlVal = $urlArray[$match[2]];
				}
			}
			//考虑列表排序按钮的效果
			else
			{
				$urlVal = IReq::get($queryKey);
			}

			if($urlVal === null && $queryVal !== '')
			{
				return '?'.$_SERVER['QUERY_STRING'].'&'.$queryKey.'='.urlencode($queryVal);
			}
			else
			{
				$fromStr = '&'.$queryKey.'='.urlencode($urlVal);
				if($queryVal === '')
				{
					$toStr   = '';
				}
				else
				{
					$toStr   = '&'.$queryKey.'='.urlencode($queryVal);
				}
				return str_replace($fromStr,$toStr,'?'.$_SERVER['QUERY_STRING']);
			}
		}
	}


	/**
	 * 用户在编辑器里上传图片
	 */
	public function upload_img_from_editor()
	{
		$checkRight = new checkRights($this);
		$checkRight->checkAdminRights();

		$photoUpload = new PhotoUpload();
		$photoUpload->setIterance(false);
		$re = $photoUpload->run();

		if(isset($re['imgFile']['flag']) && $re['imgFile']['flag']==1 )
		{
			$filePath = IUrl::creatUrl().$re['imgFile']['dir'].$re['imgFile']['name'];
			echo JSON::encode(array('error' => 0, 'url' => $filePath));
			exit;
		}
		else
		{
			$this->alert("上传失败");
		}
	}


	private function alert($msg)
	{
		header('Content-type: text/html; charset=UTF-8');
		echo JSON::encode(array('error' => 1, 'message' => $msg));
		exit;
	}

    //递归获得商品分类及子类
    static function getCategroy($category_id)
    {
    	$sub_category = '';
    	if($category_id)
    	{
    		
    		$tb_category = new IModel('category');
    		$category_info = $tb_category->query('parent_id='.$category_id);
    		if(count($category_info)>0)
    		{
    			foreach ($category_info as $value)
    			{
    				$sub_category .= $value['id'].',';
    				$sub_category .= self::getCategroy($value['id']);
    			}
    		}
    	}
    	return $sub_category;
    }
}
?>
