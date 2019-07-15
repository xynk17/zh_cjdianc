<?php
global $_GPC, $_W;
$uid=$_COOKIE["uid"];
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$GLOBALS['frames'] = $this->getNaveMenu($storeid, $action,$uid);
$type=isset($_GPC['type'])?$_GPC['type']:'today';
$pageindex = max(1, intval($_GPC['page']));
$pagesize=10;
$where=" where a.uniacid=:uniacid  and a.store_id=:store_id and a.state in (2,3) " ;
$data[':uniacid']=$_W['uniacid'];
$data[':store_id']=$storeid;
if($_GPC['time']){
  $start=strtotime($_GPC['time']['start']);
  $end=strtotime($_GPC['time']['end']);
  $where.=" and UNIX_TIMESTAMP(a.pay_time) >='{$start}' and UNIX_TIMESTAMP(a.pay_time)<='{$end}'";
  $type='all';
}else{
 if($type=='today'){
  $time=date("Y-m-d",time());
  $where.="  and a.pay_time LIKE '%{$time}%' ";
}
if($type=='yesterday'){
	$time=date("Y-m-d",strtotime("-1 day"));
 $where.="  and a.pay_time LIKE '%{$time}%' ";
}
if($type=='week'){
$time=strtotime(date("Y-m-d",strtotime("-7 day")));

  $where.=" and UNIX_TIMESTAMP(a.pay_time) >".$time;
}
if($type=='month'){
  $time=date("Y-m");
  $where.="  and a.pay_time LIKE '%{$time}%' ";
}
}
//总数统计
$sql2="select sum(money) as 'total_money' from" . tablename("cjdc_qgorder") ." as a".$where;
$list2=pdo_fetch($sql2,$data);

//获取商家手续费
$sql="select b.dn_poundage from".tablename('cjdc_store')."a  left join ".tablename('cjdc_storetype')." b on a.md_type=b.id where a.id={$storeid}";
$list4=pdo_fetch($sql);
$sql="SELECT a.* FROM ".tablename('cjdc_qgorder'). " a".$where." ORDER BY a.id DESC";
$total=pdo_fetchcolumn("SELECT count(*) FROM ".tablename('cjdc_qgorder'). " a"  .$where." ORDER BY a.id DESC",$data);
$select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
$pager = pagination($total, $pageindex, $pagesize);
$list=pdo_fetchall($select_sql,$data);
$fw_money= number_format($list4['dn_poundage']/100*($list2['total_money']),2);
if(checksubmit('export_submit', true)) {
  $start=strtotime($_GPC['time']['start']);
  $end=strtotime($_GPC['time']['end']);
  $count = pdo_fetchcolumn("SELECT COUNT(*) FROM". tablename("cjdc_qgorder")." WHERE  store_id={$storeid}  and state in (2,3) and UNIX_TIMESTAMP(pay_time) >='{$start}' and UNIX_TIMESTAMP(pay_time)<='{$end}'");
  $pagesize = ceil($count/5000);
        //array_unshift( $names,  '活动名称'); 
  $header = array(
    'item'=>'序号',
    'order_num' => '订单编号',
    'time' => '支付时间', 
    'pay_type' => '支付渠道', 
    'sh_money' => '商户实收',
    'xf_money' => '消费金额',
    'yh_money' => '优惠金额',
    'cz_money' => '储值消费',
    'jf_money' => '积分折扣金额',
    'ml_money' => '抹零金额',
    'tk_money' => '累计退款金额',
    'fw_money' => '服务费',
    'state' => '状态',
    );

  $keys = array_keys($header);
  $html = "\xEF\xBB\xBF";
  foreach ($header as $li) {
    $html .= $li . "\t ,";
  }
  $html .= "\n";
  for ($j = 1; $j <= $pagesize; $j++) {
    $sql = "select * from " . tablename("cjdc_qgorder")." WHERE  store_id={$storeid}  and state in (2,3,4,5,7,8,9,10) and UNIX_TIMESTAMP(pay_time) >='{$start}' and UNIX_TIMESTAMP(pay_time)<='{$end}' ORDER BY id DESC limit " . ($j - 1) * 5000 . ",5000 ";
    $list = pdo_fetchall($sql);            
  }

  if (!empty($list)) {
    $size = ceil(count($list) / 500);
    for ($i = 0; $i < $size; $i++) {
      $buffer = array_slice($list, $i * 500, 500);
      $user = array();
      foreach ($buffer as $k =>$row) {
        $row['item']= $k+1;
        if($row['pay_type']==1){
          $row['pay_type']='微信支付';
        }elseif($row['pay_type']==2){
          $row['pay_type']='余额支付';
        }

          $row['sh_money']=number_format($row['money']-($list4['poundage']/100*$row['money']),2);
          $row['xf_money']=number_format($row['money']+$row['discount'],2);
          $row['yh_money']=$row['discount'];
          $row['cz_money']='0.00';
          $row['jf_money']='0.00';
          $row['ml_money']='0.00';
          $row['tk_money']='0.00';
          $row['fw_money']=number_format(($list4['poundage']/100*$row['money']),2)+number_format(($sys['ps_poundage']/100*$row['ps_money']),2);    
          $row['state']='已入账';
       
   
        foreach ($keys as $key) {
          $data5[] = $row[$key];
        }
        $user[] = implode("\t ,", $data5) . "\t ,";
        unset($data5);
      }
      $html .= implode("\n", $user) . "\n";
    }
  }
  
  header("Content-type:text/csv");
  header("Content-Disposition:attachment; filename=抢购资金数据.csv");
  echo $html;
  exit();
}

include $this->template('web/dlqgzjlist');
