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
		$this->sort_type_map = array(
			'0'=> "{$this->tablePre}goods.sort ASC",
			'1'=> "{$this->tablePre}goods.volume DESC",
			'2'=> "{$this->tablePre}goods.discount ASC"
		);

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
		$sql  = "SELECT id,name FROM {$this->tablePre}category WHERE parent_id IN (SELECT id FROM {$this->tablePre}category WHERE parent_id=-1 ) ORDER BY sort ASC LIMIT 5";
		$categories =  $categoryObj->query_sql($sql);

		$goods_list = array();
		if(count($categories)>0){
			foreach($categories as $key=>$value){
				$cid = $value['id'];
				$cids = Block::getCategroy($cid);
				if(!$cids)
					continue;

				$cids = substr($cids,0,-1); 
				$sql = "SELECT DISTINCT({$this->tablePre}goods.id),{$this->tablePre}goods.*,{$this->tablePre}brand.name as bname 
				FROM {$this->tablePre}goods
				LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}goods.id = {$this->tablePre}category_extend.goods_id
				LEFT JOIN {$this->tablePre}brand ON {$this->tablePre}brand.id={$this->tablePre}goods.brand_id
				LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category_extend.category_id = {$this->tablePre}category.id
				WHERE {$this->tablePre}goods.is_del=0 AND {$this->tablePre}category_extend.category_id IN ($cids)
				ORDER BY {$this->tablePre}goods.sort ASC
				LIMIT 4";
				
				$goods =  $categoryObj->query_sql($sql);
				if(count($goods)>0){
					$goods_list[$value['id']] = $goods;
					$goods_list[$value['id']]['cname'] = $value['name'];
				}
			}
		}
	
		$data['title'] = '';
		$data['description'] = '';
		$data['keywords'] = '';
		$data['slide'] = $index_slide;
		//$data['guide_list'] = $guide_list;
		$data['goods_list'] = $goods_list;
		$data['kw'] = '';

		$this->setRenderData($data);
		$this->redirect('index');
	}

	/**
	*	列表展示
	*	@author keenhome@126.com
	*	@date 2013-4-30
	*/
	public function glist(){
		
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
			
		$word = IFilter::act(IReq::get('kw'));
		$page = IFilter::act(IReq::get('page'),'int');
		$pagesize = $this->site_config['list_num'];
		$sort = IFilter::act(IReq::get('sort'),'int');
		$order_by  = $this->sort_type_map[$sort] ? $this->sort_type_map[$sort] : "{$this->tablePre}goods.sort ASC";
		$start = $page*$pagesize;

		$all_goods_list = array();
		$goods_list = array();
		$data = array();
		$brands = array();
		$subcat = array();
		$cname = '';
		if($top_cid || $second_cid || $word){
			$categoryObj = new IModel('category');

			$where = "{$this->tablePre}goods.is_del=0";
			
			$cids = '';
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

			if($word && $word != '%' && $word != '_'){
				$where .= " AND ( {$this->tablePre}goods.name LIKE '%{$word}%' OR {$this->tablePre}goods.sellernick
 LIKE '%{$word}%' ) ";
				
				// 记录搜索词频
				//搜索关键字
				$tb_sear     = new IModel('search');
				$search_info = $tb_sear->getObj('keyword = "'.$this->word.'"','id');
				//如果是第一页，相应关键词的被搜索数量才加1
				if($search_info && $page < 2 ){
					//禁止刷新+1
					$allow_sep = "30";
					$flag = false;
					$time = ICookie::get('step');
					if(isset($time)){
						if (time() - $time > $allow_sep){
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
					$tb_sear->setData(array('keyword'=>$this->word,'num'=>1));
					$tb_sear->add();
				}

			}
				
			$all_where = $where;	
			if($bid>0){
				$where .= " AND {$this->tablePre}goods.brand_id={$bid}";
			}
			if($prid>0){
				$where .= " AND {$this->tablePre}goods.sell_price>=".$this->site_config['price_range'][$prid-1] ." AND  {$this->tablePre}goods.sell_price<=".$this->site_config['price_range'][$prid];
			}
			// 取商品总数
			$sql  = "SELECT DISTINCT({$this->tablePre}goods.id),{$this->tablePre}goods.brand_id,{$this->tablePre}category.parent_id,{$this->tablePre}category.name as cname,{$this->tablePre}category.id as cid FROM {$this->tablePre}goods
					LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}category_extend.goods_id={$this->tablePre}goods.id
					LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category.id={$this->tablePre}category_extend.category_id
					WHERE {$all_where}";
			$all_goods_list = $categoryObj->query_sql($sql); 

			$fields = " DISTINCT({$this->tablePre}goods.id),{$this->tablePre}category.parent_id,{$this->tablePre}goods.*,{$this->tablePre}category.id as cid,{$this->tablePre}brand.name as bname ";
			if($word && !$cids){
				$fields .= ",{$this->tablePre}category.name as cname";
			}
			// 获取商品列表
			$sql = "SELECT {$fields} FROM {$this->tablePre}goods
					LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}category_extend.goods_id={$this->tablePre}goods.id
					LEFT JOIN {$this->tablePre}brand ON {$this->tablePre}brand.id={$this->tablePre}goods.brand_id
					LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category.id={$this->tablePre}category_extend.category_id
					WHERE {$where}
					ORDER BY $order_by
					LIMIT $start,$pagesize";
	
			$goods_list =  $categoryObj->query_sql($sql);


			// 获取二级类的名称
			if($second_cid){
				$sql = "SELECT id,name FROM {$this->tablePre}category WHERE id={$second_cid} ORDER BY {$this->tablePre}category.sort ASC";
				$second_catinfo = $categoryObj->query_sql($sql);
				$cname = count($second_catinfo )>0 ? $second_catinfo[0]['name'] : '';

				// 获取3级类
				$sql  = "SELECT id,name FROM {$this->tablePre}category WHERE parent_id={$second_cid} ORDER BY {$this->tablePre}category.sort ASC";
				$subcat = $categoryObj->query_sql($sql);
			}
			if(!$cids && count($all_goods_list)>0){
				$top_cids = array();
				$top_cat_info = array();
				$second_cids = array();
				$second_cat_info = array();
				$third_cids = array();
				$third_cat_info = array();
				// 取顶级类
				foreach ($all_goods_list as $key => $item) {
					if($item['parent_id']==-1){
						$top_cids[$item['cid']] = $item['cid'] ;
						$top_cat_info[$item['cid']] = array('name'=>$item['cname'],'id'=>$item['cid']);
					}
				}

				foreach ($all_goods_list as $key => $item) {
					if(!$item['cid'])
						continue;
					// 取2级类
					if( in_array($item['parent_id'],$top_cids)){
						$second_cids[$item['cid']] = $item['cid'];
						$second_cat_info[$item['cid']] = array('name'=>$item['cname'],'id'=>$item['cid']);
					}else{
						$third_cids[$item['cid']] = $item['cid'];
						$third_cat_info[$item['cid']] = array('name'=>$item['cname'],'id'=>$item['cid']);
					}
				}

				if(count($third_cids)>0){
					$cids = implode(',', $third_cids);
					$subcat = $third_cat_info;
				}elseif(count($second_cids)>0){
					$cids = implode(',', $second_cids);
					$subcat = $second_cat_info;
				}elseif(count($top_cids)>0){
					$cids = implode(',', $top_cids);
					$subcat = $top_cat_info;

				}

			}
			$bids = array();
			if(count($all_goods_list)>0){
				// 取品牌id
				foreach ($all_goods_list as $key => $item) {
					if($item['brand_id']){
						$bids[$item['brand_id']] = $item['brand_id'] ;
					}
				}
			}

			// 获取所有品牌
			if(count($bids)>0){
				$bids_string = implode(',',$bids);
				$sql = "SELECT * FROM {$this->tablePre}brand WHERE id IN($bids_string) ORDER BY {$this->tablePre}brand.sort ASC";
				$brands =  $categoryObj->query_sql($sql);
				
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
		$data['kw'] = $word;
		$data['sort'] = $sort;

		$data['brands'] = count($brands)>0 ? $brands : '';
		$data['price_range']  = count($this->site_config['price_range'])>0 ? $this->site_config['price_range'] : '';
		$data['subcat']  = count($subcat)>0 ? $subcat : '';
		$data['page'] = $page;
		$data['pagesize'] = $pagesize;
		$data['goodsNum'] = count($all_goods_list);

		$data['title'] = $cname." 优淘(utao.info)商品列表";
		$data['description'] = '';
		$data['keywords'] = '';
	
		$this->setRenderData($data);
		$this->redirect('glist');
	}


	public function blist(){
		$data = array();
		$bid = IFilter::act(IReq::get('bid'));
		$page = IFilter::act(IReq::get('page'),'int');
		$pagesize = $this->site_config['list_num'];
		$start = $page*$pagesize;
		$sort = IFilter::act(IReq::get('sort'),'int');
		$order_by  = $this->sort_type_map[$sort] ? $this->sort_type_map[$sort] : "{$this->tablePre}goods.sort ASC";
		

		$goods_list = array();
		if($bid){
			$where = "{$this->tablePre}goods.is_del=0 AND {$this->tablePre}goods.brand_id={$bid}";
			$tb_goods = new IModel('goods');

			// 取商品总数
			$sql  = "SELECT DISTINCT({$this->tablePre}goods.id) 
					FROM {$this->tablePre}goods
					LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}goods.id = {$this->tablePre}category_extend.goods_id
					LEFT JOIN {$this->tablePre}brand ON {$this->tablePre}brand.id={$this->tablePre}goods.brand_id
					LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category_extend.category_id = {$this->tablePre}category.id
					WHERE {$where}";
			$all_goods_list = $tb_goods->query_sql($sql); 

			$sql = "SELECT DISTINCT({$this->tablePre}goods.id),{$this->tablePre}goods.*,{$this->tablePre}brand.name as bname 
				FROM {$this->tablePre}goods
				LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}goods.id = {$this->tablePre}category_extend.goods_id
				LEFT JOIN {$this->tablePre}brand ON {$this->tablePre}brand.id={$this->tablePre}goods.brand_id
				LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category_extend.category_id = {$this->tablePre}category.id
				WHERE {$where}
				ORDER BY {$order_by}
				LIMIT $start,$pagesize";
				$goods_list =  $tb_goods->query_sql($sql);
		}

		$data['title'] = '';
		$data['description'] = '';
		$data['keywords'] = '';
		$data['goods_list'] = $goods_list;
		$data['bname'] = count($goods_list)>0 ? $goods_list[0]['bname'] : '';
		$data['sort'] = $sort;
		$data['kw'] = '';
		$data['bid'] = $bid;
		$data['page'] = $page;
		$data['pagesize'] = $pagesize;
		$data['goodsNum'] = count($all_goods_list);

		$this->setRenderData($data);
		$this->redirect('blist');
	}


	/**
	*	列表展示
	*	@author keenhome@126.com
	*	@date 2013-4-30
	*/
	public function buy(){
		$gid = IFilter::act(IReq::get('gid'),'int');
		$tb_goods = new IModel('goods');
		//增加点击次数
		if(!ISafe::get('visit'.$gid)){
			
			$tb_goods->setData(array('click' => 'click + 1'));
			$tb_goods->update('id = '.$gid,'click');
			ISafe::set('click'.$gid,'1');
		}
		$goodsRow = $tb_goods->getObj('ID = '.$gid,'url');
		if($goodsRow['url']){
			header("Location:".$goodsRow['url']);
		}else{
			header("Location:/");
		}
	}

}
