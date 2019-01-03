<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class LiveModel extends PluginModel 
{
	public function getSet() 
	{
		global $_W;
		$set = array();
		$set = pdo_fetch('select * from ' . tablename('ewei_shop_live_setting') . ' where uniacid = :uniacid  ', array(':uniacid' => $_W['uniacid']));
		$plugin = pdo_fetch('select `name` from ' . tablename('ewei_shop_plugin') . ' where `identity` = \'live\' and isv2 = 1 and status = 1 ');
		$set['pluginname'] = $plugin['name'];
		return $set;
	}
	public function getRoom($room) 
	{
	}
	public function sendLiveMessage($openid, $type, $roomid) 
	{
		global $_W;
		global $_GPC;
		if (empty($openid) || empty($type) || empty($roomid)) 
		{
			return error(-1, '参数不全');
		}
		$room = pdo_fetch('select * from ' . tablename('ewei_shop_live') . ' where uniacid = ' . $_W['uniacid'] . ' and id = ' . $roomid . ' ');
		$time = date('Y-m-d H:i', time());
		$datas[] = array('name' => '通知类型', 'value' => '已订阅');
		$datas[] = array('name' => '时间', 'value' => $time);
		if ($type == 'livefollow') 
		{
			$datas[] = array('name' => '任务名称', 'value' => '【' . $room['title'] . '】订阅成功');
			$url = mobileUrl('live/room', array('id' => $roomid), true);
			$tag = 'livefollow';
			$remark = "\n" . '<a href=\'' . $url . '\'>点击查看详情</a>';
			$text = '您已订阅【' . $room['title'] . '】！' . "\n" . $remark;
			$message = array( 'first' => array('value' => '您好，您有新的直播订阅', 'color' => '#ff0000'), 'keyword1' => array('title' => '任务名称', 'value' => '直播间订阅消息提醒', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '已订阅', 'color' => '#000000'), 'remark' => array('value' => $text . "\n" . '感谢您的支持', 'color' => '#000000') );
		}
		else if ($type == 'liveroom') 
		{
			$room['livetime'] = date('Y-m-d H:i', $room['livetime']);
			$datas[] = array('name' => '任务名称', 'value' => '您订阅的【' . $room['title'] . '】将在' . $room['livetime'] . '开播！');
			$url = str_replace('addons/ewei_shopv2/plugin/live/task/', '', $_W['siteroot']);
			$url = $url . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=ewei_shopv2&do=mobile&r=live.room&id=' . $room['id'];
			$tag = 'livefollow';
			$remark = "\n" . '<a href=\'' . $url . '\'>点击查看详情</a>';
			$text = '您订阅的【' . $room['title'] . '】将在' . $room['livetime'] . '开播！' . "\n" . $remark;
			$message = array( 'first' => array('value' => '您好，您订阅的直播有新消息', 'color' => '#ff0000'), 'keyword1' => array('title' => '任务名称', 'value' => '您订阅的【' . $room['title'] . '】将在' . $room['livetime'] . '开播！', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '已订阅', 'color' => '#000000'), 'remark' => array('value' => $text . "\n" . '感谢您的支持', 'color' => '#000000') );
		}
		m('notice')->sendNotice(array('openid' => $openid, 'tag' => $tag, 'default' => $message, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas));
	}
	public function isLiving($roomid = 0) 
	{
		global $_W;
		if (!(empty($roomid))) 
		{
			$live = pdo_fetch('SELECT id FROM ' . tablename('ewei_shop_live') . ' WHERE id=:id AND uniacid=:uniacid AND status=1 AND living=1 LIMIT 1', array(':id' => $roomid, ':uniacid' => $_W['uniacid']));
			if (!(empty($live))) 
			{
				return true;
			}
		}
		return false;
	}
	public function isFavorite($openid = NULL, $roomid = 0) 
	{
		global $_W;
		if (!(empty($openid)) && !(empty($roomid))) 
		{
			$favorite = pdo_fetch('SELECT id FROM ' . tablename('ewei_shop_live_favorite') . 'WHERE uniacid=:uniacid AND roomid=:roomid AND openid=:openid AND `deleted`=0 LIMIT 1', array(':uniacid' => $_W['uniacid'], ':roomid' => $roomid, ':openid' => $openid));
			if (!(empty($favorite))) 
			{
				return true;
			}
		}
		return false;
	}
	public function getEmoji() 
	{
		return array('', '微笑', '撇嘴', '色', '发呆', '流泪', '害羞', '闭嘴', '睡', '大哭', '尴尬', '发怒', '调皮', '呲牙', '惊讶', '难过', '冷汗', '抓狂', '吐', '偷笑', '愉快', '白眼', '傲慢', '饥饿', '困', '惊恐', '流汗', '憨笑', '大兵', '奋斗', '咒骂', '疑问', '嘘', '晕', '抓狂', '哀', '敲打', '再见', '擦汗', '抠鼻', '糗', '坏笑', '左哼哼', '右哼哼', '哈欠', '鄙视', '委屈', '快哭了', '阴险', '亲亲', '惊吓', '可怜', '拥抱', '月亮', '太阳', '炸弹', '骷髅', '菜刀', '猪头', '西瓜', '咖啡', '饭', '爱心', '强', '弱', '握手', '胜利', '抱拳', '勾引', '好的', '差劲', '玫瑰', '凋谢', '吻', '爱情', '飞吻');
	}
	public function getLiveList() 
	{
		return array('panda' => '熊猫直播', 'douyu' => '斗鱼直播', 'huajiao' => '花椒直播', 'yizhibo' => '一直播', 'inke' => '映客直播', 'shuidi' => '水滴直播', 'qlive' => '青果直播', 'ys7' => '萤石直播');
	}
	public function emoji2html($str) 
	{
		$emojiList = $this->getEmoji();
		foreach ($emojiList as $index => $emoji ) 
		{
			while ($emoji == $str) 
			{
				return '<img class="face" src="../addons/ewei_shopv2/plugin/live/static/images/face/' . $index . '.gif" />';
			}
		}
		return '[' . $str . ']';
	}
	public function handleRecords($roomid = 0, $manage = false) 
	{
		global $_W;
		if ($manage) 
		{
			$uid = 'console' . '_' . $_W['uid'] . '_' . $_W['role'] . '_' . $_W['uniacid'];
		}
		$table = $this->getRedisTable('chat_records', $roomid);
		$records = redis()->lRange($table, 0, ($manage ? 100 : 30));
		if (empty($records)) 
		{
			return array();
		}
		if ($manage) 
		{
			$table_banned = 'ewei_shop_live_banned_' . $roomid;
			$bannedArr = array();
		}
		foreach ($records as &$record ) 
		{
			if (empty($record)) 
			{
				continue;
			}
			$record = json_decode($record, true);
			if (($record['type'] == 'image') && !(empty($record['text']))) 
			{
				$imgurl = tomedia($record['text']);
				if ($manage) 
				{
					$record['text'] = '<a href="' . $imgurl . '" target="_blank"><img src="' . $imgurl . '"/></a>';
				}
				else 
				{
					$record['text'] = '<img src="' . $imgurl . '"/>';
				}
			}
			else if ($record['type'] == 'redpack') 
			{
				if ($manage) 
				{
					$record['text'] = '[余额红包] ' . $record['text'];
				}
				else 
				{
					$record['text'] = '<div class="redpack" data-pushid="1">' . $record['text'] . '</div>';
				}
			}
			else 
			{
				$_this = $this;
				$record['text'] = preg_replace_callback('/\\[([^\\]]+)\\]/', function($matches) use(&$_this) 
				{
					return $_this->emoji2html($matches[1]);
				}
				, $record['text']);
				$atText = '';
				if (!(empty($record['at']))) 
				{
					$atUsers = iunserializer($record['at']);
					if (!(empty($atUsers))) 
					{
						foreach ($atUsers as $key => $nickname ) 
						{
							$atText .= '<span class="nickname';
							if ($key == $uid) 
							{
								$atText .= ' self';
							}
							$atText .= '" data-uid="' . $key . '" data-nickname="' . $nickname . '">@';
							if ($key == $uid) 
							{
								$atText .= '你';
							}
							else 
							{
								$atText .= $nickname;
							}
							$atText .= ' </span>';
						}
					}
				}
				$record['text'] = $atText . $record['text'];
			}
			if ($record['status'] == 1) 
			{
				$record['text'] = (($record['mid'] == $uid ? '你' : '"' . $record['nickname'] . '"'));
				$record['text'] .= '撤回了一条消息';
			}
			else if ($record['status'] == 2) 
			{
				if ($manage) 
				{
					$record['text'] = (($record['mid_manage'] == $uid ? '你' : $record['nickname_manage']));
					$record['text'] .= '删除了"' . $record['nickname'] . '"的一条消息';
				}
				else if ($record['mid'] == $uid) 
				{
					$record['text'] = '管理员"' . $record['nickname_manage'] . '"删除了你一条消息';
				}
				else 
				{
					$record['text'] = '"' . $record['nickname'] . '"撤回了一条消息';
				}
			}
			if ($manage) 
			{
				$uuid = $record['mid'];
				if (isset($bannedArr[$uuid])) 
				{
					$record['banned'] = 1;
				}
				if (redis()->hExists($table_banned, $uuid)) 
				{
					$record['banned'] = 1;
					$bannedArr[$uuid] = 1;
				}
			}
		}
		unset($record, $openid, $imgurl);
		return $records;
	}
	public function getLiveInfo($url = NULL, $type = 'auto') 
	{
		if (empty($url)) 
		{
			return error(1, '视频地址为空');
		}
		$liveList = $this->getLiveList();
		if ($type == 'auto') 
		{
			foreach ($liveList as $key => $val ) 
			{
				while (strexists($url, $key)) 
				{
					if (($key == 'huajiao') && strexists($url, 'shuidi')) 
					{
						continue;
					}
					$type = $key;
					break;
				}
			}
			if ($type == 'auto') 
			{
				return error(1, '未自动识别到视频来源');
			}
		}
		$resultArr = array();
		load()->func('communication');
		switch ($type) 
		{
			case 'panda': preg_match('/.*panda.tv\\/(\\d+)/is', $url, $matchs);
			if (empty($matchs)) 
			{
				return error(1, '视频地址参数错误或所选来源错误');
			}
			$roomid = $matchs[1];
			if (strpos($matchs[0], 'xingyan') !== false) 
			{
				$apiResult = ihttp_get('http://m.api.xingyan.panda.tv/room/baseinfo?xid=' . $roomid);
			}
			else 
			{
				$apiResult = ihttp_get('https://room.api.m.panda.tv/index.php?callback=&method=room.shareapi&roomid=' . $roomid);
			}
			$apiResult = json_decode($apiResult['content'], true);
			if (!(empty($apiResult['errno']))) 
			{
				return error(2, '获取房间信息失败');
			}
			if (strpos($matchs[0], 'xingyan') !== false) 
			{
				$resultArr = array('status' => ($apiResult['data']['roominfo']['playstatus'] == 1 ? 1 : 0), 'poster' => $apiResult['data']['roominfo']['photo'], 'hls_url' => $apiResult['data']['videoinfo']['hlsurl']);
			}
			else 
			{
				$resultArr = array('status' => ($apiResult['data']['roominfo']['status'] == 2 ? 1 : 0), 'poster' => $apiResult['data']['roominfo']['pictures']['img'], 'hls_url' => $apiResult['data']['videoinfo']['address']);
			}
			break;
			case 'douyu': preg_match('/.*douyu.com\\/(\\d+)/is', $url, $matchs);
			if (empty($matchs)) 
			{
				return error(1, '视频地址参数错误或所选来源错误');
			}
			$roomid = $matchs[1];
			$apiResult = ihttp_get('https://m.douyu.com/html5/live?roomId=' . $roomid);
			$apiResult = json_decode($apiResult['content'], true);
			if (empty($apiResult['data'])) 
			{
				return error(2, '获取房间信息失败');
			}
			if (!(empty($apiResult['error']))) 
			{
				return error(2, $apiResult['data']);
			}
			$resultArr = array('status' => ($apiResult['data']['error'] == 0 ? 1 : 0), 'poster' => '', 'hls_url' => $apiResult['data']['hls_url']);
			if (!(empty($resultArr['status']))) 
			{
				$html = $apiResult = ihttp_get('https://m.douyu.com/' . $roomid);
			}
			break;
			case 'huajiao': preg_match('/.*huajiao.com\\/l\\/(\\d+)/is', $url, $matchs);
			if (empty($matchs)) 
			{
				return error(1, '视频地址参数错误或所选来源错误');
			}
			$roomid = $matchs[1];
			$apiResult = ihttp_get('http://h.huajiao.com/l/index?liveid=' . $roomid);
			$html = $apiResult['content'];
			preg_match('@"feed":(.*?)"title"@is', $html, $feedInfo);
			if (empty($feedInfo)) 
			{
				return error(2, '获取房间信息失败');
			}
			$feedInfo = rtrim($feedInfo[1], ',');
			$feedInfo .= '}';
			$feedInfo = json_decode($feedInfo, true);
			$resultArr = array('status' => ($feedInfo['paused'] == 'N' ? 1 : 0), 'poster' => $feedInfo['image'], 'hls_url' => (!(empty($feedInfo['m3u8'])) ? $feedInfo['m3u8'] : 'http://qh.cdn.huajiao.com/live_huajiao_v2/' . $feedInfo['sn'] . '/index.m3u8'));
			break;
			case 'yizhibo': preg_match('/\\/l\\/(.*?).html/is', $url, $matchs);
			if (empty($matchs)) 
			{
				return error(1, '视频地址参数错误或所选来源错误');
			}
			$roomid = $matchs[1];
			$apiResult = ihttp_get('http://www.yizhibo.com/l/' . $roomid . '.html');
			$html = $apiResult['content'];
			preg_match('@play_url:"(.*?)",@is', $html, $hls_url);
			preg_match('@covers:"(.*?)",@is', $html, $poster);
			preg_match('@status:(.*?),@is', $html, $status);
			$resultArr = array('status' => ($status[1] == 10 ? 1 : 0), 'poster' => $poster[1], 'hls_url' => $hls_url[1]);
			break;
			case 'inke': preg_match('/.*id=(\\d+)/is', $url, $matchs);
			if (empty($matchs)) 
			{
				return error(1, '视频地址参数错误或所选来源错误');
			}
			$roomid = $matchs[1];
			$roomInfo = ihttp_get('http://webapi.busi.inke.cn/mobile/Get_live_addr?liveid=' . $roomid);
			$roomInfo = json_decode($roomInfo['content'], true);
			if (empty($roomInfo) || !(empty($roomInfo['error_code']))) 
			{
				return error(2, '获取房间信息失败');
			}
			$resultArr = array('status' => $roomInfo['data']['status']);
			if (!(empty($roomInfo['data']['status']))) 
			{
				$resultArr['hls_url'] = $roomInfo['data']['live_addr'][0]['hls_stream_addr'];
				$resultArr['hls_url'] = str_replace('rtmp://', 'http://', $resultArr['hls_url']);
				$resultArr['rtmp_url'] = $roomInfo['data']['file'][0];
				$userInfo = ihttp_get('http://webapi.busi.inke.cn/mobile/user_info?liveid=' . $roomid);
				$userInfo = json_decode($userInfo['content'], true);
				if (!(empty($userInfo)) && empty($userInfo['error_code']) && !(empty($userInfo['data']))) 
				{
					$resultArr['poster'] = $userInfo['data']['image'];
				}
			}
			break;
			case 'shuidi': preg_match('/.*view.html\\?.*sn=([0-9A-Z]*)/i', $url, $matchs);
			if (empty($matchs)) 
			{
				return error(1, '视频地址参数错误或所选来源错误');
			}
			$roomid = $matchs[1];
			$apiResult = ihttp_get('https://live2.jia.360.cn/public/getInfoAndPlayV2?from=mpc_ipcam_web&sn=' . $roomid);
			$apiResult = json_decode($apiResult['content'], true);
			if (empty($apiResult['publicInfo'])) 
			{
				return error(2, '获取房间信息失败');
			}
			$resultArr = array('status' => $apiResult['publicInfo']['online'], 'poster' => $apiResult['playInfo']['imageUrl'], 'hls_url' => $apiResult['playInfo']['hls'], 'rtmp_url' => $apiResult['playInfo']['rtmp']);
			break;
			case 'qlive': preg_match('/.*channel\\?id=(\\d+)/is', $url, $matchs);
			if (empty($matchs)) 
			{
				return error(1, '视频地址参数错误或所选来源错误');
			}
			$roomid = $matchs[1];
			$apiResult = ihttp_get('https://qlive.163.com/live/square/cameraDetail?deviceId=' . $roomid . '&relateNum=0');
			$apiResult = json_decode($apiResult['content'], true);
			if (empty($apiResult['result']['cameraDetail'])) 
			{
				return error(2, '获取房间信息失败');
			}
			$resultArr = array('status' => $apiResult['result']['cameraDetail']['canView'], 'poster' => $apiResult['result']['cameraDetail']['coverFileName'], 'hls_url' => 'http://v.smartcamera.163.com/qingguo-public/' . $roomid . '/playlist.m3u8');
			break;
			case 'ys7': preg_match('/.*\\?cameraId=(\\d+)/is', $url, $matchs);
			if (empty($matchs)) 
			{
				return error(1, '视频地址参数错误或所选来源错误');
			}
			$roomid = $matchs[1];
			$apiResult = ihttp_post('http://square.ys7.com/H5/square/get', array('ids' => $roomid));
			$apiResult = json_decode($apiResult['content'], true);
			if (empty($apiResult['data'])) 
			{
				return error(2, '获取房间信息失败');
			}
			$apiResult = $apiResult['data'][0];
			$resultArr = array('status' => $apiResult['isPlay'], 'poster' => $apiResult['videoCoverUrl'], 'hls_url' => $apiResult['hlsVideoUrl']);
			break;
		}
		$resultArr['type'] = $type;
		$resultArr['typeName'] = $liveList[$type];
		return $resultArr;
	}
	public function getRedisTable($table, $roomid) 
	{
		return 'ewei_shop_live_' . $table . '_' . $roomid;
	}
	public function deleteRedisTable($roomid) 
	{
		if (empty($roomid)) 
		{
			return;
		}
		$table_settings = $this->getRedisTable('settings', $roomid);
		redis()->del($table_settings);
		$table_online = $this->getRedisTable('room', $roomid);
		redis()->del($table_online);
		$table_banned = $this->getRedisTable('banned', $roomid);
		redis()->del($table_banned);
		return;
		foreach (redis() as $index => $record ) 
		{
			$record = json_decode($record, true);
			if (empty($record)) 
			{
				continue;
			}
			if ($record['type'] == 'redpack') 
			{
				$table_redpack = $this->getRedisTable('redpack_' . $record['time'], $roomid);
				redis()->del($table_redpack);
				$table_redpack_list = $this->getRedisTable('redpack_list_' . $record['time'], $roomid);
				redis()->del($table_redpack_list);
				$table_redpack_order = $this->getRedisTable('redpack_order_' . $record['time'], $roomid);
				redis()->del($table_redpack_order);
			}
			else if ($record['type'] == 'coupon') 
			{
			}
		}
	}
	public function liveprice($goods = array(), $roomid = 0) 
	{
		global $_W;
		if (empty($goods) || empty($roomid)) 
		{
			return false;
		}
		if (floatval($goods['liveprice']) <= 0) 
		{
			return false;
		}
		if (floatval($goods['minprice']) <= floatval($goods['liveprice'])) 
		{
			return false;
		}
		$live = pdo_fetch('SELECT id, title FROM ' . tablename('ewei_shop_live') . ' WHERE uniacid=:uniacid AND id=:id AND status=1 AND living=1 LIMIT 1', array(':uniacid' => $_W['uniacid'], ':id' => $roomid));
		if (empty($live)) 
		{
			return false;
		}
		return array('name' => $live['title'], 'price' => $goods['liveprice'], 'id' => $roomid);
	}
	public function getWsAddress() 
	{
		$file = EWEI_SHOPV2_CORE . 'socket/socket.config.php';
		if (!(is_file($file))) 
		{
			return false;
		}
		require_once $file;
		$ws = ((SOCKET_SERVER_SSL ? 'wss://' : 'ws://'));
		return $ws . SOCKET_CLIENT_IP . ':' . SOCKET_SERVER_PORT;
	}
}
?>