<?php
require_once('../../../include/config.inc.php');
require_once('../../../include/common.inc.php');
require_once('../../../include/module/zns_user.inc.php');
require_once('../../../include/database.inc.php');

/*
1.获取文件列表		?act=get_list
				参数:无
				返回值：{error: 0|1, desc: 错误详情, list: [{ID: 笔记ID, title: 标题, catalog: 类目, mod_time: 修改时间}]}
				
2.添加笔记		?act=add
				参数：content, title, catalog
				返回值：{error: 0|1, desc: 错误详情, ID: 笔记ID, create_time: 时间, mod_time: 时间}
				
3.获取笔记		?act=get
				参数：ID
				返回值：{ID: 笔记ID, title: 标题, mod_time: 修改时间, create_time: 创建时间, content: 内容, catalog: 类目}

4.删除			?act=del
				参数：ID
				返回值：{error: 0|1}

5.查找			?act=find
				参数：key=关键字
				返回值：同get的，只是不是所有的，而是查找到的

6.修改			?act=mod
				参数：ID, content, title, catalog
				返回值：{error: 0|1}
*/

session_start();

$user=new ZnsUser();
$user->loadFromSession();

$user_id=$user->data['ID'];

$db=$__g_db__;

$act=$_GET['act'];

switch($act)
{
	case 'add':
		$user->isLogin() || die(arr2Json(array('error'=>1, 'desc'=>'未登录')));
		
		//正式开始添加
		$now=time();
		$data=array(
			'user_id'		=>	$user->data['ID'],
			'title'			=>	$_GET['title'],
			'mod_time'		=>	$now,
			'create_time'	=>	$now,
			'content'		=>	$_GET['content'],
			'catalog'		=>	$_GET['catalog']
		);
		
		$data['ID']=$db->insert('nb_note', $data);
		
		echo arr2Json($data);
		break;
	case 'get_list':
		$user->isLogin() || die(arr2Json(array('error'=>1, 'desc'=>'未登录')));
		
		//正式开始查找所有文件
		$sql="SELECT ID, title, catalog, mod_time FROM nb_note";
		$res=$db->query($sql);
		
		if($res)
		{
			$a=array();
			
			for($i=0;$i<count($res);$i++)
				array_push($a, arr2Json($res[$i]));
			
			echo '{error: 0, list: ['.implode(',', $a).']}';
		}
		else
			echo arr2Json(array('error'=>1, 'desc'=>'未知错误'));
		break;
	case 'get':
		$user->isLogin() || die(arr2Json(array('error'=>1, 'desc'=>'未登录')));
		
		//正式开始查找
		$id=$_GET['id'];
		$res=$db->getOne('nb_note', 'ID, title, mod_time, create_time, content, catalog', "ID={$id}");
		
		if($res)
			echo arr2Json(array_merge($res, array('error'=>0)));
		else
			echo arr2Json(array('error'=>1, 'desc'=>'未找到'));
		break;
	case 'del':
		$id=$_GET['id'];
		if($db->delete('nb_note', "ID={$id}"))
			echo arr2Json(array('error'=>0));
		else
			echo arr2Json(array('error'=>1, 'desc'=>'删除失败'));
		break;
	case 'find':
		$key=$_GET['key'];
		
		$key || die(arr2Json(array('error'=>1, 'desc'=>'需要指定关键字')));
		
		//开始搜索
		$sql="SELECT ID, title, mod_time, create_time, content, catalog FROM nb_note WHERE title LIKE {$key} OR content LIKE {$key}";
		$res=$db->query($sql);
		
		if($res)
		{
			$a=array();
			
			for($i=0;$i<count($res);$i++)
				array_push($a, arr2Json($res[$i]));
			
			echo '{error: 0, list: ['.implode(',', $a).']}';
		}
		else
			echo arr2Json(array('error'=>1, 'desc'=>'未知错误'));
		break;
	case 'mod':
		$db->update('nb_note', array(
			'title'		=>	$_GET['title'],
			'content'	=>	$_GET['content'],
			'catalog'	=>	$_GET['catalog'],
			'mod_time'	=>	time()
		), "ID={$id} AND user_id={$user_id}");
		break;
	default:
		die(arr2Json(array(
			'error'=>1, 'desc'=>'未知的命令：'.$act
		)));
}
?>