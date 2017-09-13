<?php
//提交信息抽红包
public function redpack(){
	
	//用户openid,至于怎么获取用户opendid,则不在本文讨论范围
	$openid = 'oOeQMuMH7ZFyauOxuTXc0qwKRONU';

	//调用红包方法
	$result = $this->sendredpack($openid);

	//微信红包接口返回的是xml格式，需要先转化为数组
	//禁止引用外部xml实体
	libxml_disable_entity_loader(true);
	//先把xml转换为simplexml对象，再把simplexml对象转换成 json，再将 json 转换成数组。
	$result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
	
	//你可以把返回的结果打印出来看一下
	// echo '<pre>';
	// print_r($res);

	//处理业务逻辑,注意返回结果中的result_code才是红包发放状态
	if ($result['result_code'] == 'SUCCESS') {
		echo '红包发放成功';
	}else{
		echo '红包发放失败';
	}
}

//发放现金红包
private function sendredpack($openid){
   	
   	//微信商户现金红包接口url
    $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
   
   	//准备你的红包参数
    $mch_billno = '填写你的商户号'.date("YmdHis",time()).rand(1000,9999); //商户订单号
    $mch_id = '填写你的商户号';                                           //微信支付分配的商户号
    $wxappid = '你的公号appid';                                           //公众账号appid
    $send_name = "商户名称";                                              //商户名称
    $re_openid = $openid;                                                 //用户openid
    $total_amount = "100";                                                // 付款金额,单位分,最小100,否则会发放失败
    $total_num = 1;                                                       //红包发放总人数
    $wishing = "恭喜发财";                                                //红包祝福语
    $client_ip = "";                                                      //调用微信红包接口的Ip地址，公号后台设置
    $act_name = "关注有礼";                                               //活动名称
    $remark = "测试";                                                     //备注
    $apikey = "";                                                // key 商户后台设置的  微信商户平台(pay.weixin.qq.com)-->账户设置-->API安全-->密钥设置
    $nonce_str =  md5(rand());                                            //随机字符串，不长于32位
    $m_arr = array (
            'mch_billno' => $mch_billno,
            'mch_id' => $mch_id,
            'wxappid' => $wxappid,
            'send_name' => $send_name,
            're_openid' => $re_openid,
            'total_amount' => $total_amount,
            'total_num' => $total_num,
            'wishing' => $wishing,
            'client_ip' => $client_ip,
            'act_name' => $act_name,
            'remark' => $remark,
            'nonce_str'=> $nonce_str
    );

    //生成签名
    array_filter ( $m_arr ); // 清空参数为空的数组元素
    ksort ( $m_arr ); // 按照参数名ASCII码从小到大排序
           
    $stringA = "";
    foreach ( $m_arr as $key => $row ) {
        $stringA.="&".$key.'='.$row;
    }
    $stringA = substr($stringA,1);
    // 拼接API密钥：
    $stringSignTemp = $stringA."&key=".$apikey;
    $sign = strtoupper(md5($stringSignTemp));

    $textTpl = '<xml>
                <sign><![CDATA[%s]]></sign>
                <mch_billno><![CDATA[%s]]></mch_billno>
                <mch_id><![CDATA[%s]]></mch_id>
                <wxappid><![CDATA[%s]]></wxappid>
                <send_name><![CDATA[%s]]></send_name>
                <re_openid><![CDATA[%s]]></re_openid>
                <total_amount><![CDATA[%s]]></total_amount>
                <total_num><![CDATA[%s]]></total_num>
                <wishing><![CDATA[%s]]></wishing>
                <client_ip><![CDATA[%s]]></client_ip>
                <act_name><![CDATA[%s]]></act_name>
                <remark><![CDATA[%s]]></remark>
                <nonce_str><![CDATA[%s]]></nonce_str>
                </xml>';
 	$resultStr = sprintf($textTpl, $sign, $mch_billno, $mch_id, $wxappid, $send_name,$re_openid,$total_amount,$total_num,$wishing,$client_ip,$act_name,$remark,$nonce_str);
  	return $this->curl_post_ssl($url, $resultStr);
}

//curl调用微信红包接口
private function curl_post_ssl($url, $vars, $second=30,$aHeader=array())
{
    $ch = curl_init();
    //超时时间
    curl_setopt($ch,CURLOPT_TIMEOUT,$second);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
    //这里设置代理，如果有的话
    //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
    //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
   
    //以下两种方式需选择一种
    //第一种方法，cert 与 key 分别属于两个.pem文件
    //默认格式为PEM，可以注释
    //需要特别注意的是curl是系统应用,所以它的默认路径是从linux系统根目录开始的,注意填写正确的证书路径
    curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
    curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/apiclient_cert.pem');
    curl_setopt($ch,CURLOPT_SSLKEY,getcwd().'/apiclient_key.pem');
    //部分服务器需要验证根证书，不需要的可以注释
    curl_setopt($ch,CURLOPT_CAINFO,'/rootca.pem');
   
    //第二种方式，两个文件合成一个.pem文件
    //curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');
 
    if( count($aHeader) >= 1 ){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
    }
 
    curl_setopt($ch,CURLOPT_POST, 1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
    $data = curl_exec($ch);
    if($data){
        curl_close($ch);
        return $data;
    }else{
        $error = curl_errno($ch);
        echo "call faild, errorCode:$error\n";
        curl_close($ch);
        return false;
    }
}