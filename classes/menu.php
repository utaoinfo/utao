<?php
/**
 * @copyright Copyright(c) 2011 jooyea.net
 * @file menu.php
 * @brief 后台系统菜单管理
 * @author webning
 * @date 2011-01-12
 * @version 0.6
 * @note
 */
/**
 * @brief Menu
 * @class Menu
 * @note
 */
class Menu
{
	private static $commonMenu = array('/system/default');
	public $current;
    //菜单的配制数据
	private static $menu = array(
		'商品'=>array(
				'商品管理'=>array(
					'/goods/goods_list' => '商品列表',
					'/goods/goods_add' => '商品添加'
				),
				'商品分类'=>array(
					'/goods/category_list'	=>	'分类列表',
					'/goods/category_edit'	=>	'添加分类'
				),
				'品牌'=>array(
					'/brand/brand_list'		=>	'品牌列表'
				),
				'搜索'=>array(
					'/tools/keyword_list' => '关键词列表',
					'/tools/search_list' => '搜索统计'
				)
		),

		'会员'=>array(
				'会员管理'=>array(
            		'/member/member_list' => '会员列表',
             		'/member/group_list' => '会员组列表'
				),
				'信息处理' => array(
					'/comment/suggestion_list'  => '建议管理',
					'/comment/comment_list'		=> '评价管理',
					'/comment/message_list'		=> '站内消息',
				),
				'邮件短信设置'=>array(
					'/message/tpl_list'		=>	'模板管理',
					'/message/registry_list'=>	'邮件订阅'
				)
		),

        '系统'=>array(
        		'后台首页'=>array(
        			'/system/default' => '后台首页',
        		),
            	'网站管理'=>array(
            		'/system/conf_base' => '网站设置',
            	),
            	'多平台登录'=>array(
                '/system/oauth_list' => '平台列表'
            	),
            	'权限管理'=>array(
            		'/system/admin_list' => '管理员',
            		'/system/role_list'  => '角色',
            		'/system/right_list' => '权限资源'
            	)
		),

       '工具'=>array(
				'数据库管理'=>array(
					'/tools/db_bak' => '数据库备份',
					'/tools/db_res' => '数据库还原',
				),
				'文章管理'=>array(
					'/tools/article_cat_list'=> '文章分类',
					'/tools/article_list'=> '文章列表'
				),

				'帮助管理'=>array(
       			'/tools/help_cat_list'=> '帮助分类',
       			'/tools/help_list'=> '帮助列表'
       			),

       			'广告管理'=>array(
       			'/tools/ad_position_list'=> '广告位列表',
       			'/tools/ad_list'=> '广告列表'
       			),

       			'公告管理'=>array(
       			'/tools/notice_list'=> '公告列表',
       			'/tools/notice_edit'=> '公告发布'
       			),
         		'网站地图'=>array(
                '/tools/seo_sitemaps' => '网站搜索地图',
				)
		)
	);

	private static $menu_non_display = array(
		'/tools/article_edit_act'=>'/tools/article_list',
		'/message/notify_filter' =>'/message/notify_list',
		'/system/navigation' => '/system/conf_none_exists',
		'/system/navigation_edit' => '/system/conf_none_exists',
		'/system/navigation_recycle' => '/system/conf_none_exists',
		'/member/recycling' => '/member/member_list',
		'/system/navigation' => '/system/default',
		'/system/navigation_edit' => '/system/default',
		'/system/navigation_recycle' => '/system/default',
	);

    /**
     * @brief 根据用户的权限过滤菜单
     * @return array
     */
    private function filterMenu()
    {
    	$rights = ISafe::get('admin_right');

		//如果不是超级管理员则要过滤菜单
		if($rights != 'administrator')
		{
			foreach(self::$menu as $firstKey => $firstVal)
			{
				if(is_array($firstVal))
				{
					foreach($firstVal as $secondKey => $secondVal)
					{
						if(is_array($secondVal))
						{
							foreach($secondVal as $thirdKey => $thirdVal)
							{
								if(!in_array($thirdKey,self::$commonMenu) && (stripos(str_replace('@','/',$rights),','.substr($thirdKey,1).',') === false))
								{
									unset(self::$menu[$firstKey][$secondKey][$thirdKey]);
								}
							}
							if(empty(self::$menu[$firstKey][$secondKey]))
							{
								unset(self::$menu[$firstKey][$secondKey]);
							}
						}
					}
					if(empty(self::$menu[$firstKey]))
					{
						unset(self::$menu[$firstKey]);
					}
				}
			}
		}
    }

    /**
     * @brief 取得当前菜单应该生成的对应JSON数据
     * @return Json
     */
	public function submenu()
	{
		$controllerObj = IWeb::$app->getController();
		$controller = $controllerObj->getId();
		$actionObj = $controllerObj->getAction();
		$action = $actionObj->getId();
		$this->current = '/'.$controller.'/'.$action;
		$this->vcurrent = '/'.$controller.'/';
		$items  = array();

		if(isset(self::$menu_non_display[$this->current]))
		{
			$this->current = self::$menu_non_display[$this->current];
			$tmp = explode("/",$this->current);
			$this->vcurrent = $tmp[1];
			$action = $tmp[2];
		}

		//过滤菜单
		$this->filterMenu();
		$find_current = false;
		$items = array();
		foreach(self::$menu as $key => $value)
		{
			if(!is_array($value))
			{
				return;
			}
			$item = array();
			$item['current'] = false;
			$item['title'] = $key;

			foreach($value as $big_cat_name => $big_cat)
			{
				foreach($big_cat as $link=>$title)
				{
					if(!isset($item['link']) )
					{
						$item['link'] = $link;
					}

					if($find_current)
					{
						break;
					}

					$tmp1 = explode('_',$action);
					$tmp1 = $tmp1[0];
					if($link == $this->current || preg_match("!^/[^/]+/{$tmp1}_!",$link) )
					{
						$item['current'] = $find_current = true;
						foreach($value as $k=>$v)
						{
							foreach($v as $subMenuKey=>$subMenuName)
							{
								$tmpUrl = IUrl::creatUrl($subMenuKey);
								unset($value[$k][$subMenuKey]);
								$value[$k][$tmpUrl]['name'] = $subMenuName;
								$value[$k][$tmpUrl]['urlPathinfo'] = $subMenuKey;
							}
						}
						$item['list'] = $value;
					}
				}

				if($find_current)
				{
					break;
				}
			}
			$item['link'] = IUrl::creatUrl($item['link']);
			$items[] = $item;
		}
		return JSON::encode($items);
	}
}
?>
