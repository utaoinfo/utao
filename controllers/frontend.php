<?php
/**
 * @copyright Copyright(c) 2011 jooyea.net
 * @file site.php
 * @brief
 * @author webning
 * @date 2011-03-22
 * @version 0.6
 * @note
 */
/**
 * @brief Site
 * @class Site
 * @note
 */
class Frontend extends IController
{
    public $layout='frontend';
    private $tablePre = '';
	function init(){
		$this->tablePre = isset(IWeb::$app->config['DB']['tablePre']) ? IWeb::$app->config['DB']['tablePre'] : '';
		// 获取导航配置
		$guideObj       = new IModel('guide');
		$guide_list = $guideObj->query('','`order`,`name`,`link`','`order`','desc');
		if(count($guide_list)>0){
			$this->guide_list = $guide_list;
		}

		$siteConfigObj = new Config("site_config");
		$site_config   = $siteConfigObj->getInfo();
		$this->site_config = $site_config;

	}

	public function index(){
		$data = array();

		$index_slide = isset($this->site_config['index_slide'])? unserialize($this->site_config['index_slide']) :array();

		// 获取各个类型商品前4
		// 获取二级类
		$categoryObj = new IModel('category');

		// 获取前四个分类
		$sql  = "SELECT id,name FROM {$this->tablePre}category WHERE parent_id IN (SELECT id FROM {$this->tablePre}category WHERE parent_id=0 ) ORDER BY sort ASC LIMIT 5";
		$categories =  $categoryObj->query_sql($sql);

		$goods_list = array();
		if(count($categories)>0){
			foreach($categories as $key=>$value){
				$cid = $value['id'];
				$cids = Block::getCategroy($cid);
				if(!$cids)
					continue;

				$cids = substr($cids,0,-1); 
				$sql = "SELECT DISTINCT({$this->tablePre}goods.id),{$this->tablePre}goods.name,{$this->tablePre}goods.notes,{$this->tablePre}goods.sell_price,{$this->tablePre}goods.market_price,{$this->tablePre}goods.from,{$this->tablePre}goods.list_img,{$this->tablePre}goods.url 
				FROM {$this->tablePre}goods
				LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}goods.id = {$this->tablePre}category_extend.goods_id
				LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category_extend.category_id = {$this->tablePre}category.id
				WHERE {$this->tablePre}goods.is_del=0 AND {$this->tablePre}category_extend.category_id IN ($cids)
				ORDER BY {$this->tablePre}goods.sort ASC
				LIMIT 5";
				
				$goods =  $categoryObj->query_sql($sql);
				if(count($goods)>0){
					$goods_list[$value['id']] = $goods;
					$goods_list[$value['id']]['cname'] = $value['name'];
				}
			}
		}
	
		$data['title'] = isset($site_config['index_seo_title'])? $site_config['index_seo_title']:'';
		$data['description'] = isset($site_config['index_seo_description'])? $site_config['index_seo_description']:'';
		$data['keywords'] = isset($site_config['index_seo_keywords'])? $site_config['index_seo_keywords']:'';
		$data['slide'] = $index_slide;
		//$data['guide_list'] = $guide_list;
		$data['goods_list'] = $goods_list;

		$this->setRenderData($data);
		$this->redirect('index');
	}

	/**
	*	列表展示
	*	@author keenhome@126.com
	*	@date 2013-4-30
	*/
	public function glist(){
		$data = array();
		$goods_list = array();
		$ids = IFilter::act(IReq::get('ids'));
		if($ids){
			$arr_ids = explode('_', $ids);
			$top_cid = intval($arr_ids[0]);
			$second_cid = intval($arr_ids[1]);
			$third_cid = intval($arr_ids[2]);
			$forth_cid = intval($arr_ids[3]);
			$bid = intval($arr_ids[4]);
			$prid = intval($arr_ids[5]);
		}else{
			$top_cid = 0;
			$second_cid = 0;
			$third_cid = 0;
			$forth_cid = 0;
			$bid = 0;
			$prid = 0;
		}
			

		$page = IFilter::act(IReq::get('page'),'int');
		$pagesize = $this->site_config['list_num'];
		$start = $page*$pagesize;
		$end = ($page+1)*$pagesize;
		$brands = array();
		$subcat = array();
		$cname = '';
		if($top_cid || $second_cid){
			$categoryObj = new IModel('category');
			// 获取二级类的名称
			$sql = "SELECT id,name FROM {$this->tablePre}category WHERE id=$second_cid ORDER BY {$this->tablePre}category.sort ASC";
			$second_catinfo = $categoryObj->query_sql($sql);
			$cname = count($second_catinfo )>0 ? $second_catinfo[0]['name'] : '';

			$where = "{$this->tablePre}goods.is_del=0";
			if($third_cid){
				$cids = Block::getCategroy($third_cid);
			}elseif($second_cid){
				$cids = Block::getCategroy($second_cid);
			}elseif($top_cid){
				$cids = Block::getCategroy($top_cid);
			}
			if($cids){
				$cids = substr($cids,0,-1); 
				$where .= " AND {$this->tablePre}category_extend.category_id IN ({$cids})";
			}
				
				
			if($bid>0){
				$where .= " AND {$this->tablePre}goods.brand_id={$bid}";
			}
			if($prid>0){
				$where .= " AND {$this->tablePre}goods.sell_price>=".$this->site_config['price_range'][$prid-1] ." AND  {$this->tablePre}goods.sell_price<=".$this->site_config['price_range'][$prid];
			}
			// 取商品总数
			$sql  = "SELECT DISTINCT({$this->tablePre}goods.id) FROM {$this->tablePre}goods
					LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}category_extend.goods_id={$this->tablePre}goods.id
					LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category.id={$this->tablePre}category_extend.category_id
					WHERE {$where}";
			$all_goods_list = $categoryObj->query_sql($sql); 


			// 获取商品列表
			$sql = "SELECT DISTINCT({$this->tablePre}goods.id),{$this->tablePre}goods.*,{$this->tablePre}category.id as cid FROM {$this->tablePre}goods
					LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}category_extend.goods_id={$this->tablePre}goods.id
					LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category.id={$this->tablePre}category_extend.category_id
					WHERE {$where}
					ORDER BY {$this->tablePre}goods.sort ASC
					LIMIT $start,$end ";
				
			$goods_list =  $categoryObj->query_sql($sql);

			// 获取当前类别的一级子类
			$sql  = "SELECT id,name FROM {$this->tablePre}category WHERE parent_id={$second_cid}";
			$subcat = $categoryObj->query_sql($sql);

			// 获取品牌id
			if( ($second_cid || $third_cid) && $cids ){
				$bids = array();
				$sql = "SELECT DISTINCT({$this->tablePre}goods.brand_id), {$this->tablePre}category.* FROM {$this->tablePre}goods
					LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}category_extend.goods_id={$this->tablePre}goods.id
					LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category.id={$this->tablePre}category_extend.category_id
					WHERE {$this->tablePre}category_extend.category_id IN ({$cids}) AND  {$this->tablePre}goods.is_del=0 ";
					$brand_ids =  $categoryObj->query_sql($sql);

				foreach($brand_ids as $key=>$value){
					if($value['brand_id']){
						array_push($bids, $value['brand_id']);
					}
				}	
				// 获取所有品牌
				if(count($bids)>0){
					$bids_string = implode(',',$bids);
					$sql = "SELECT * FROM {$this->tablePre}brand WHERE id IN($bids_string) ORDER BY {$this->tablePre}brand.sort ASC";
					$brands =  $categoryObj->query_sql($sql);
				}
			}
		}
		
		$data['goods_list'] = $goods_list;

		$data['cname'] = $cname;
		$data['top_cid'] = $top_cid;
		$data['second_cid'] = $second_cid;
		$data['third_cid'] = $third_cid;
		$data['forth_cid'] = $forth_cid;
		$data['bid'] = $bid;
		$data['prid'] = $prid;
		$data['kw'] = '';

		$data['brands'] = count($brands)>0 ? $brands : '';
		$data['price_range']  = count($this->site_config['price_range'])>0 ? $this->site_config['price_range'] : '';
		$data['subcat']  = count($subcat)>0 ? $subcat : '';
		$data['page'] = $page;
		$data['goodsNum'] = count($all_goods_list);
	
		$this->setRenderData($data);
		$this->redirect('glist');
	}


	/**
	*	搜索
	*	@author keenhome@126.com
	*	@date 2013-4-30
	*/
	public function search(){
		$data = array();
		$goods_list = array();
		$ids = IFilter::act(IReq::get('ids'));
		if($ids){
			$arr_ids = explode('_', $ids);
			$top_cid = intval($arr_ids[0]);
			$second_cid = intval($arr_ids[1]);
			$third_cid = intval($arr_ids[2]);
			$forth_cid = intval($arr_ids[3]);
			$bid = intval($arr_ids[4]);
			$prid = intval($arr_ids[5]);
		}else{
			$top_cid = 0;
			$second_cid = 0;
			$third_cid = 0;
			$forth_cid = 0;
			$bid = 0;
			$prid = 0;
		}
			
		$brands = array();
		$subcat = array();
		$cname = '';

		$page = IFilter::act(IReq::get('page'),'int');
		$word = IFilter::act(IReq::get('kw'));
		$cat_id = intval(IReq::get('ids'));

		if($word != '' && $word != '%' && $word != '_'){
			$goodsObj      = new IModel('goods');
			// 获取商品列表
			$sql = "SELECT * FROM {$this->tablePre}goods WHERE {$this->tablePre}goods.name LIKE '%{$word}%' AND {$this->tablePre}goods.is_del=0 ORDER BY {$this->tablePre}goods.sort ASC";
			$goods_list = $goodsObj->query_sql($sql);
			// 商品总数
			

			if(count($goods_list)>0){
				$brand_ids = array();
				$goods_ids = array();
				foreach ($goods_list as $key => $goods) {
					if($goods['brand_id'])
						$brand_ids[$goods['brand_id']] = $goods['brand_id'];
					array_push($goods_ids, $goods['id']);
				}
				// 获取分类
				if(count($goods_ids)){
					$goods_ids_string = implode(',',$goods_ids);
					$sql = "SELECT id,name,parent_id FROM {$this->tablePre}category WHERE  {$this->tablePre}category.id IN (SELECT {$this->tablePre}category_extend.category_id FROM {$this->tablePre}category_extend WHERE {$this->tablePre}category_extend.goods_id IN($goods_ids_string) )";
					$categories = $goodsObj->query_sql($sql);
					if(count($categories)>0){
						$parent1_categories = array();
						$second_categories =array();
						$subcat = array();
						// 获取上一级类
						foreach ($categories as $key => $category) {
							if($category['parent_id']){
								$parent1_categories[$category['id']] = $category['id'];
							}
						}
						//print_r($parent1_categories);exit();
					}
				}
				// 获取品牌
				if(count($brand_ids)){
					$bids_string = implode(',',$brand_ids);
					$sql = "SELECT * FROM {$this->tablePre}brand WHERE id IN($bids_string) ORDER BY {$this->tablePre}brand.sort ASC ";
					$brands =  $goodsObj->query_sql($sql);
				}

			}

			//搜索关键字
			$tb_sear     = new IModel('search');
			$search_info = $tb_sear->getObj('keyword = "'.$word.'"','id');
			//如果是第一页，相应关键词的被搜索数量才加1
			if($search_info && $page < 2 ){
				//禁止刷新+1
				$allow_sep = "30";
				$flag = false;
				$time = ICookie::get('step');
				if(isset($time)){
					if (time() - $time > $allow_sep)
					{
						ICookie::set('step',time());
						$flag = true;
					}
				}else{
					ICookie::set('step',time());
					$flag = true;
				}
				if($flag){
					$tb_sear->setData(array('num'=>'num + 1'));
					$tb_sear->update('id='.$search_info['id'],'num');
				}
			}elseif( !$search_info ){
				//如果数据库中没有这个词的信息，则新添
				$tb_sear->setData(array('keyword'=>$word,'num'=>1));
				$tb_sear->add();
			}
		}else{
			IError::show(403,'请输入正确的查询关键词');
		}

		$data['goods_list'] = $goods_list;
		$data['cname'] = $cname;
		$data['top_cid'] = $top_cid;
		$data['second_cid'] = $second_cid;
		$data['third_cid'] = $third_cid;
		$data['forth_cid'] = $forth_cid;
		$data['bid'] = $bid;
		$data['prid'] = $prid;
		$data['brands'] = count($brands)>0 ? $brands : '';
		$data['price_range']  = count($this->site_config['price_range'])>0 ? $this->site_config['price_range'] : '';
		$data['subcat']  = count($subcat)>0 ? $subcat : '';
		$data['page'] = $page;
		$data['kw'] = $word;
		$data['goodsNum'] = count($goods_list);
		$this->setRenderData($data);
		$this->redirect('glist',false);
	}


}
