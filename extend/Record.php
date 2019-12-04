<?php
    namespace app\package\controller;
    use think\Controller;
    use think\Session;
    use think\Db;
    use think\Request;
    use app\package\model\Record as RecordModel;
    use app\package\model\Image as ImageModel;
    class Record extends Base
    {
        // 循环渲染
        public function record_list(){
           
            $res=RecordModel::all(function($query){
                $query->with('ImageModel')->where("r_id",">",0)->order('r_id', 'desc');
            });

            if( is_array( $res->toArray()) )
            {
                // dump($res);
                $this-> view ->assign('list',$res);
            }else{
                foreach ($res as $val) {
                   $list[]=$val->toArray();
                }
                $this-> view ->assign('list',$list);
             }
            return $this -> view -> fetch ('record/record_list');
            // dump($res->toArray());
        }
   
        public function uploadsMost(Request $image){
                $data=$image->param();
                $r_id=$data['r_id'];
                $files = request()->file('files');
                $v_img=$files;
                if($v_img){  
                    $v_img = $v_img->move(ROOT_PATH . 'public/static/image/package/','');
                    if($v_img){
                        $v_url='image/package/';
                        $v_img=$v_img->getSaveName();
                        $d=['i_url'=>$v_url.$v_img,'r_id'=>$r_id];
                        $res=Db::name('image');
                        $res->insert($d);
                    }else{
                        // 上传失败获取错误信息
                        echo $files->getError();
                    }
                }
                return $r_id;
        }

        //插入record
        public function insert_record(Request $request){
            $data=$request->param();
            $record=new RecordModel();
            $record->data([
                'p_id'=>$data["p_id"],
                'u_id'=>$data["u_id"],
                'title'=>$data["title"],
                'record'=>$data["record"],
            ]);
            $record->save();
           return  $record->r_id;
        }
        //生成报告
        
        public  function report(Request $request){
            $req=$request->param();
            $id=$req["id"];
            $timestampe=time();
            $noncestr="wanzhengxin";
            $url= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
            $jsapi_ticket=$this->jsapi_ticket();
            $str="jsapi_ticket=".$jsapi_ticket."&noncestr=".$noncestr."&timestamp=".$timestampe."&url=".$url;
            $signature=sha1($str);
            $this->assign('url', $url);
            $this->assign('id', $id);
            $this->assign('noncestr', $noncestr);
            $this->assign('timestampe', $timestampe);
            $this->assign('jsapi_ticket', $jsapi_ticket);
            $this->assign('signature', $signature);
            return $this -> view -> fetch ('record/report');
        }
      
                public function getToken(){
            $burl="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx7316676cbe8e5c4b&secret=6dfc4a96ff94897912e5ef97f18399d9";
            $access_token_array = $this->get_curl_json($burl);
            $access_token = $access_token_array['access_token']; 
            return $access_token;
        }
        public function jsapi_ticket(){
            $ticket=Session::get('ticket');
            if($ticket==null){
                $access_token=$this->getToken();
                $jsapi_ticket="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi";
                $post_data="{'code':1}";
                $json = $this->api_notice_increment($jsapi_ticket, $post_data);
                $info=json_decode($json,true);
                $ticket=$info['ticket'];
                Session::set('ticket',$ticket);
                return $ticket;
            }else{
                return $ticket;
            }
        }
        public function api_notice_increment($url, $data){
            $ch = curl_init();
            $header = "Accept-Charset: utf-8";
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            //curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $tmpInfo = curl_exec($ch);
            if (curl_errno($ch)) {
                curl_close( $ch );
                return $ch;
            }else{
                curl_close( $ch );
                return $tmpInfo;
            }

        }
          //删除
        public function del_record(Request $request){
            $res=$request->param();
            $id=$res['id'];
            $p=new RecordModel();
            $pdata =$p->where('r_id','=',$id)->delete();
            return 1;
        }
        //curl操作,获取返回值,为数组类型
        public function get_curl_json($url){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
            $result = curl_exec($ch);
            if(curl_errno($ch)){
                print_r(curl_error($ch));
            }
            curl_close($ch);
            return json_decode($result,TRUE);
        }
    }