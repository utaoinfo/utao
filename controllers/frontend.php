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
			
		$word = IFilter::act(IReq::get('kw'));
		$page = IFilter::act(IReq::get('page'),'int');
		$pagesize = $this->site_config['list_num'];
		$start = $page*$pagesize;

		$all_goods_list = array();
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
				$where .= " AND {$this->tablePre}goods.name LIKE '%{$word}%'";
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

			$fields = " DISTINCT({$this->tablePre}goods.id),{$this->tablePre}category.parent_id,{$this->tablePre}goods.*,{$this->tablePre}category.id as cid ";
			if($word && !$cids){
				$fields .= ",{$this->tablePre}category.name as cname";
			}
			// 获取商品列表
			$sql = "SELECT {$fields} FROM {$this->tablePre}goods
					LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}category_extend.goods_id={$this->tablePre}goods.id
					LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category.id={$this->tablePre}category_extend.category_id
					WHERE {$where}
					ORDER BY {$this->tablePre}goods.sort ASC
					LIMIT $start,$pagesize";
	
			$goods_list =  $categoryObj->query_sql($sql);


			// 获取二级类的名称
			if($second_cid){
				$sql = "SELECT id,name FROM {$this->tablePre}category WHERE id={$second_cid} ORDER BY {$this->tablePre}category.sort ASC";
				$second_catinfo = $categoryObj->query_sql($sql);
				$cname = count($second_catinfo )>0 ? $second_catinfo[0]['name'] : '';

				// 获取3级类
				$sql  = "SELECT id,name FROM {$this->tablePre}category WHERE parent_id={$second_cid}";
				$subcat = $categoryObj->query_sql($sql);
			}
	
			if(!$cids && count($goods_list)>0){
				$top_cids = array();
				$top_cat_info = array();
				$second_cids = array();
				$second_cat_info = array();
				$third_cids = array();
				$third_cat_info = array();
				// 取顶级类
				foreach ($goods_list as $key => $item) {
					if($item['parent_id']==0){
						$top_cids[$item['cid']] = $item['cid'] ;
						$top_cat_info[$item['cid']] = $item;
					}
				}

				foreach ($goods_list as $key => $item) {
					// 取2级类
					if( in_array($item['parent_id'],$top_cids)){
						$second_cids[$item['cid']] = $item['cid'];
						$second_cat_info[$item['cid']] = $item;
					}else{
						$third_cids[$item['cid']] = $item['cid'];
						$third_cat_info[$item['cid']] = $item;
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



			// 获取品牌id
			if( $cids ){
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
		$data['kw'] = $word;

		$data['brands'] = count($brands)>0 ? $brands : '';
		$data['price_range']  = count($this->site_config['price_range'])>0 ? $this->site_config['price_range'] : '';
		$data['subcat']  = count($subcat)>0 ? $subcat : '';
		$data['page'] = $page;
		$data['goodsNum'] = count($all_goods_list);
	
		$this->setRenderData($data);
		$this->redirect('glist');
	}


}
