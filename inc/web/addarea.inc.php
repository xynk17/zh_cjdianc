<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$info = pdo_get('cjdc_area',array('uniacid' => $_W['uniacid'],'id'=>$_GPC['id']));
	if(checksubmit('submit')){
			$data['num']=$_GPC['num'];
			$data['area_name']=$_GPC['area_name'];
			$data['uniacid']=$_W['uniacid'];
			if($_GPC['id']==''){				
				$res=pdo_insert('cjdc_area',$data);
				if($res){
					message('添加成功',$this->createWebUrl('area',array()),'success');
				}else{
					message('添加失败','','error');
				}
			}else{
				$res = pdo_update('cjdc_area', $data, array('id' => $_GPC['id']));
				if($res){
					message('编辑成功',$this->createWebUrl('area',array()),'success');
				}else{
					message('编辑失败','','error');
				}
			}
		}
include $this->template('web/addarea');