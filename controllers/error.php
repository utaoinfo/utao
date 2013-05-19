<?php
/**
 * @copyright (c) 2011 jooyea.net
 * @file error.php
 * @brief 错误处理类
 * @author chendeshan
 * @date 2010-12-16
 * @version 0.6
 */
class Error extends IController
{
	function error404()
	{
		$this->redirect('/404.html');
	}

	function error403($data)
	{
		if(is_array($data))
		{
			$data = isset($data['message']) ? $data['message'] : '';
		}

		//必须用?的形式，%2f 会激活apache的mod_rewrite的一些小bug
		$this->redirect('/site/error/?msg='.urlencode($data));
	}

	function error503()
	{
		$data = '您无权限操作此功能';
		$this->redirect('/block/error?msg='.urlencode($data));
	}
}


?>
