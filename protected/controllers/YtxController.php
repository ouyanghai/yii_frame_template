<?php 
/* 
*提供58，慧聪等资源给营天下手机
*  
*/
class YtxController extends TopController{
    
    public function init(){
        parent::init();
    }
    
    //提供58城市
    public function actionCity(){
        $city = $this->getTree(0, 'b2b_city');
        $city = json_encode($city);
        echo isset($_GET['callback']) ? $_GET['callback'] . '('. $city .')' : $city;
    }
    
    //提供58类别
    public function actionCategory(){
        $command = Yii::app()->db->createCommand();
        $category = $command->setText("select id as value, name as text from b2b_category where pid=0 and is_hide=0")->queryAll();
        $category = json_encode($category);
        echo isset($_GET['callback']) ? $_GET['callback'] . '('. $category .')' : $category;
    }
    
    //提供手机号码
    public function actionMobile(){
        $command = Yii::app()->db->createCommand();
        $mobile = array();
        if( isset($_GET['tid'], $_GET['cid'], $_GET['offset']) ){
            $tid = (int)$_GET['tid'];
            $cid = (int)$_GET['cid'];
            $offset = (int)$_GET['offset'];
            $limit = 100;
            
            $subCates = $command->setText("select id from b2b_category where pid={$cid}")->queryColumn();
            if( empty($subCates) ){
                $condition = " and cid={$cid}";
            }else{
                $str = implode(',', $subCates);
                $condition = " and cid in ({$str})";
            }
            
            $sql = "select phone from b2b_company where char_length(phone)=11 and phone like '1%' and tid={$tid} {$condition} limit {$offset}, {$limit}";
            $mobile = $command->setText($sql)->queryColumn();
        }
        $mobile = json_encode($mobile);
        echo isset($_GET['callback']) ? $_GET['callback'] . '('. $mobile .')' : $mobile;
    }
    
    private function getChild(&$arr, $id){
        $childs=array();
        foreach ($arr as $k => $v){
            if($v['pid']== $id){
                $childs[]=$v;
            }
        }
        return $childs;
    }
    
    private function getTree($pid=0){
        global $rows;
        if( gettype($rows) != 'array' ){
            $num = func_num_args();
            $args = func_get_args();
            
            $command = Yii::app()->db->createCommand();
            $sql = "select id as value, name as text, pid from {$args[1]}";
            $rows = $command->setText($sql)->queryAll();
        }
        
        $childs = $this->getChild($rows, $pid);
        
        if( empty($childs) )
            return null;
        
        foreach ($childs as $k => $v){
            $rescurTree=$this->getTree($v['value']);
            if( null !=  $rescurTree){ 
                $childs[$k]['children']=$rescurTree;
            }
        }
        return $childs;
    }
    
    //接收文章数据
    public function actionData(){
        $post = $_POST;
        
        if( !isset($post['title'], $post['content']) )
            exit('参数错误，请重试！');
        
        $shopId = preg_match('/app\./Ui', $_SERVER['HTTP_HOST']) ? 8244 : 19322334;
        $pid = isset($post['company']) ? $post['company'] : $shopId;        //公司编号
        $cname = '互联网';     //类别名
        $tname = '深圳';      //城市名
        $cid = 2;       //类别编号
        $tid = 702;      //城市编号
        
        $data = array(
            'title' => addslashes(htmlspecialchars($post['title'])),
            'cname' => $cname,
            'tname' => $tname,
            'cid' => $cid,
            'tid' => $tid,
            'pid' => $pid,
            'create_time' => time(),
        );
        
        $db = Yii::app()->db;
        $command = $db->createCommand();
        $command->insert('b2b_info', $data);
        $infoId = $db->lastInsertId;
        
        $descData = array(
            'pid' => $infoId,
            'desc' => $post['content'],
        );
        
        $rtn = $command->insert('b2b_desc', $descData);
        echo $rtn;
        
    }
    
    //任务管理接口
    public function actionDataApi(){
        $command = Yii::app()->db->createCommand();
        if( !isset($_GET['sql']) ){
            echo '无效的参数';
            Yii::app()->end();
        }
        
        $sql = $_GET['sql'];
        $limit = 13;
        $page = !empty($_GET['p']) ? (int)$_GET['p'] : 1;
        $callback = !empty($_GET['callback']) ? $_GET['callback'] : '';
        
        $operates = array('select', 'insert', 'update', 'delete');
        $operate = strtolower(substr($sql, 0,  6));
        if( !in_array($operate, $operates) ){
            echo '无效的操作';
            Yii::app()->end();
        }
        
        if( $operate == 'select' ){
            $query = $sql . ' order by id desc limit :offset, :limit';
            if( $page == 'all' ){       //不分页
                $query = $sql . ' order by id desc';
            }
            $command->setText($query);
            
            if( $page != 'all' ){       //不分页
                $command->bindValue(':offset', ($page - 1) * $limit);
                $command->bindValue(':limit', $limit);
            }
            $result = $command->queryAll();
            
            $arr = explode('from', $sql);
            $table = $arr[1];
            $count = $command->setText("select count(*) as num from {$table}")->queryScalar();
            $result = array('data'=>$result, 'total'=>$count, 'page'=>$page);
        }else{
            $result = $command->setText($sql)->execute();
        }
        
        echo !empty($callback) ? $callback . "(". json_encode($result) .")" : json_encode($result);
    }
    
    //显示分组及分组下的所有设备
    public function actionDevices(){
        $callback = !empty($_GET['callback']) ? $_GET['callback'] : '';
        
        $command = Yii::app()->db->createCommand();
        $groups = $command->setText("select * from ytx_group where mode=1 order by id desc")->queryAll();
        $devices = $command->setText("select * from ytx_device order by id desc")->queryAll();
        foreach($devices as $device){
            foreach($groups as $k=>$group){
                if( $device['group_id'] == $group['id'] ){
                    $groups[$k]['children'][] = $device;
                }
            }
        }
        
        echo !empty($callback) ? $callback . "(". json_encode($groups) .")" : json_encode($groups);
    }
    
    //显示分组及分组下的所有脚本
    public function actionScripts(){
        $command = Yii::app()->db->createCommand();
    }
    
    //接收数据
    public function actionReceiveData(){
        $post = $_POST;
        print_r($post);
        //echo '<script>window.opener.location="http://app.task.com/index.php?r=task";window.close();</script>';
    }
    
    //测试
    public function actionTest(){
        $get = $_GET;
        if( !isset($get['imei'], $get['timestamp']) )
            exit('参数错误');
        
        $time = time();
        $arr = array(
            'datetime' => $time,
            'level' => 2,
        );
        echo isset($get['callback']) ? $get['callback'] . '('. json_encode($arr) .')' : json_encode($arr);
    }
    
    //得到58手机app类目
    public function actionMobileCategory(){
        /*$category = $this->getTree(0, 'ytx_58category');
        $category = json_encode($category);
        echo isset($_GET['callback']) ? $_GET['callback'] . '('. $category .')' : $category;*/
        $category = $this->getTree(0, 'b2b_category');
        foreach($category as $k => $v){
            if( isset($v['children']) ){
                $category[$k]['children'] = array_merge( $category[$k]['children'] );
            }
        }
        $category = json_encode($category);
        echo isset($_GET['callback']) ? $_GET['callback'] . '('. $category .')' : $category;
    }
    
    //得到58定制版手机app类目
    public function actionMobileCategorydz(){
        /*$category = $this->getTree(0, 'ytx_58category');
        $category = json_encode($category);
        echo isset($_GET['callback']) ? $_GET['callback'] . '('. $category .')' : $category;*/
        $category = $this->getTree(0, 'b2b_category');
        foreach($category as $k => $v){
            if( isset($v['children']) ){
                $category[$k]['children'] = array_merge(array(array('value' => '0', 'text' => '全部', 'pid' => $v['pid'])), $category[$k]['children']);
            }else{
                $category[$k]['children'] = array(array('value' => '0', 'text' => '全部', 'pid' => $v['pid']));
            }
        }
        $category = json_encode($category);
        echo isset($_GET['callback']) ? $_GET['callback'] . '('. $category .')' : $category;
    }
    
    //得到百姓手机app类目
    public function actionBxwCategory(){
        $category = $this->getTree(0, 'bxw_category');
        foreach($category as $k => $v){
            if( isset($v['children']) ){
                $category[$k]['children'] = array_merge(array(array('value' => '0', 'text' => '全部', 'pid' => $v['pid'])), $category[$k]['children']);
            }
        }
        $category = json_encode($category);
        echo isset($_GET['callback']) ? $_GET['callback'] . '('. $category .')' : $category;
    }
    
    //接收营天下手机数据，保存到数据库
    public function actionSaveData(){
        $get = $_GET;
        if( !isset($get['phone']) || !isset($get['cityname']) || !isset($get['companyname']) || !isset($get['catename']) ){
            echo '参数错误';
            Yii::app()->end();
        }
        
        $command = Yii::app()->db->createCommand();
        $data = array(
            'company' => addslashes($get['companyname']),
            'phone' => addslashes(trim($get['phone'])),
            'city_name' => addslashes($get['cityname']),
            'cate_name' => addslashes($get['catename']),
            'add_time' => time(),
        );
        $city_id = $command->setText("select id from b2b_city where name = '{$data['city_name']}'")->queryScalar();
        $cate_id = $command->setText("select id from b2b_category where name = '{$data['cate_name']}'")->queryScalar();
        $sql = "insert ignore into b2b_company (name, phone, create_time, tid, cid) values ('{$data['company']}', '{$data['phone']}', '{$data['add_time']}', '{$city_id}', '{$cate_id}')";
        $command->setText($sql)->execute();
    }
    
    public function actionMcategory(){
        $command = Yii::app()->db->createCommand();
        $get = $_GET;
        $pid = 0;
        
        if( isset($get['pname']) && !empty($get['pname']) ){
            $pname = $get['pname'];
            $pid = $command->setText("select id from b2b_category where name = '{$pname}'")->queryScalar();
        }
        
        $data = array(
            'name' => $get['name'],
            'pid' => $pid
        );
        //$command->insert('b2b_category', $data);
        $sql = "insert ignore into b2b_category (name, pid) values ('{$data['name']}', {$data['pid']})";
        $command->setText($sql)->execute();
    }
    
}