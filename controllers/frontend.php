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
		
		$word = IFilter::act(IReq::get('kw'));
		$ids = IFilter::act(IReq::get('ids'),'string');
		$arr_ids = $ids ? explode('_', $ids) : array();

		$top_cid = isset($arr_ids[0]) ? intval($arr_ids[0]) : 0 ;
		$second_cid = isset($arr_ids[1]) ? intval($arr_ids[1]) : 0 ;
		$third_cid = isset($arr_ids[2]) ? intval($arr_ids[2]) : 0 ;
		$forth_cid = isset($arr_ids[3]) ? intval($arr_ids[3]) : 0 ;
		$bid = isset($arr_ids[4]) ? intval($arr_ids[4]) : 0 ;
		$prid = isset($arr_ids[5]) ? intval($arr_ids[5]) : 0 ;
		$prid = $prid> count($this->site_config['price_range'])-1 ? count($this->site_config['price_range'])-1 : $prid;
		$sort = isset($arr_ids[6]) ? intval($arr_ids[6]) : 0 ;
		$sort = $sort> (count($this->sort_type_map)-1) ? count($this->sort_type_map)-1 : $sort;
		$page = isset($arr_ids[7]) ? intval($arr_ids[7]) : 0 ;

		$pagesize = $this->site_config['list_num'];
		$order_by  = $this->sort_type_map[$sort] ? $this->sort_type_map[$sort] : "{$this->tablePre}goods.sort ASC";
		$start = $page*$pagesize;

		$all_goods_list = array();
		$total_num = array();
		$goods_list = array();
		$data = array();
		$brands = array();
		$subcat = array();
		$cname = '';
		$title = '';
		$description = '';
		$keywords = '';

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
			// 取所有商品基本信息
			$sql  = "SELECT DISTINCT({$this->tablePre}goods.id),{$this->tablePre}goods.brand_id,{$this->tablePre}category.parent_id,{$this->tablePre}category.name as cname,{$this->tablePre}category.id as cid FROM {$this->tablePre}goods
					LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}category_extend.goods_id={$this->tablePre}goods.id
					LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category.id={$this->tablePre}category_extend.category_id
					WHERE {$all_where}";
			$all_goods_list = $categoryObj->query_sql($sql); 

			// 取分页总数
			$sql = "SELECT DISTINCT({$this->tablePre}goods.id) FROM {$this->tablePre}goods
					LEFT JOIN {$this->tablePre}category_extend ON {$this->tablePre}category_extend.goods_id={$this->tablePre}goods.id
					LEFT JOIN {$this->tablePre}category ON {$this->tablePre}category.id={$this->tablePre}category_extend.category_id
					WHERE {$where}";
			$total_num = $categoryObj->query_sql($sql); 

			$fields = " DISTINCT({$this->tablePre}goods.id),
						{$this->tablePre}category.parent_id,
						{$this->tablePre}goods.*,
						{$this->tablePre}category.id as cid,
						{$this->tablePre}brand.name as bname ";
			if($word && !$cids){
				$fields .= ",{$this->tablePre}category.name as cname";
			}

			if(!$cids && $third_cid){
				
				$where .= " AND {$this->tablePre}category_extend.category_id=({$third_cid})";
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
				$sql = "SELECT id,name,title,keywords,descript 
						FROM {$this->tablePre}category 
						WHERE id={$second_cid} 
						ORDER BY {$this->tablePre}category.sort ASC";
				$second_catinfo = $categoryObj->query_sql($sql);


				if(count($second_catinfo )>0){
					$cname = $second_catinfo[0]['name'];
					$title = $second_catinfo[0]['title'] ? '【'.$cname.'】' .$second_catinfo[0]['title'] : '' ;
					$description = $second_catinfo[0]['descript'];
					$keywords = $second_catinfo[0]['keywords'];
				}

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
		$data['goodsNum'] = count($total_num);

		$data['title'] = $title ? $title : '【'.$cname.'】' .'商品列表-优加网(ujia.info)';
		$data['description'] = $description;
		$data['keywords'] = $keywords;
	
		$this->setRenderData($data);
		$this->redirect('glist');
	}


	public function blist(){
		$data = array();
		$ids =  IFilter::act(IReq::get('ids'));
		if($ids){
			$arr_ids = explode('_', $ids);
			$bid = isset($arr_ids[0]) ? intval($arr_ids[0]) : 0;
			$sort = isset($arr_ids[1]) ? intval($arr_ids[1]) : 0;
			$page = isset($arr_ids[2]) ? intval($arr_ids[2]) : 0;
		}else{
			$bid = IFilter::act(IReq::get('bid'));
			$page = IFilter::act(IReq::get('page'),'int');
			$sort = IFilter::act(IReq::get('sort'),'int');
		}
		$sort = $sort> (count($this->sort_type_map)-1) ? count($this->sort_type_map)-1 : $sort;
		$pagesize = $this->site_config['list_num'];
		$start = $page*$pagesize;
		
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

		$data['title'] =  count($goods_list)>0 ? '品牌'.$goods_list[0]['bname'].'商品列表' : '';
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
		if(count($goodsRow)>0 && $goodsRow['url']){
			header("Location:".$goodsRow['url']);
		}else{
			header("Location:/");
		}
	}

	//咨询详情页面
	public function article() {
		$data = array();
		$this->article_id = IFilter::act(IReq::get('id'),'int');
		if($this->article_id == ''){
			IError::show(404,'缺少咨询ID参数');
		}else{
			$articleObj       = new IModel('article');
			$this->articleRow = $articleObj->getObj('id = '.$this->article_id);
			if(empty($this->articleRow)){
				IError::show(404,'资讯文章不存在');
				exit;
			}

			//关联商品
			$relationObj = new IQuery('relation as r');
			$relationObj->join   = ' left join goods as go on r.goods_id = go.id ';
			$relationObj->where  = ' r.article_id = '.$this->article_id.' and go.id is not null ';

			$this->relationList  = $relationObj->find();
			$data['articleRow'] = $this->articleRow;
			$data['title'] = count($this->articleRow)>0 ? $this->articleRow['title'] : '';
			$data['description'] = count($this->articleRow)>0 ? $this->articleRow['description'] : '';
			$data['keywords'] = count($this->articleRow)>0 ? $this->articleRow['keywords'] : '';
			$data['kw'] = '';
			$this->setRenderData($data);
			$this->redirect('article');
		}
	}


}
