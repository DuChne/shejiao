<?php
/**
 *
 * Created by PhpStorm.
 * User: tanshenxiao
 * Date: 2018/12/27
 * Time: 19:34
 */

use think\Validate;

class Home_EweiShopV2Page extends MobileLoginPage
{
    /**
     * 获取用 能人号 和 资讯
     */

    public function getArtile()
    {
        global $_W;
        global $_GPC;
        $type = isset($_GEC['type']) and !empty($_GPC['type']) ? $_GPC['type'] : 1;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $list = db('article')->where(['uniacid' => $_W['uniacid'],'status' => 2])->field('head_img,title,content,created_time')->limit(($pindex - 1) * $psize,$psize)->order('sort desc,created_time desc')->select();


        show_json(1,['list' => $list]);
    }

    /**
     * 获取文章详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDetail()
    {
        global $_W;
        global $_GPC;

        $id = $_GPC['id'];
        $data = db('article a')->where(['a.uniacid' => $_W['uniacid'],'a.status' => 2])->field('a.*,b.merchname,IFNULL(c.disable,-1) as disable_good,IFNULL(d.disable,-1) as disable_collection')
            ->join('ewei_shop_merch_user b','a.merchid = b.id')
            ->leftJoin('article_record c','a.id = c.article_id and c.type = 1')
            ->leftJoin('article_record d','a.id = d.article_id and d.type = 2')->find();

        if (false == $data) {
            show_json(0,'文章相亲获取失败了。');
        }

        //支付成功更新阅读量
        db('article')->where(['id' => $id])->setInc('read_num');

        show_json(1,['data' => $data]);
    }

    /**
     * 点赞 收藏 记录
     * type 1点赞 2收藏
     * disable 0点赞或收藏 1 取消点赞或收藏
     */
    public function article_record()
    {
        global $_GPC;
        global $_W;
        $data = [
            'type' => $_GPC['type'],
            'disable' => $_GPC['disable'],
        ];
        $validate = new Validate(['type|类型' => 'require|in:1,2','disable' => 'require|in:0,1']);
        if (!$validate->check($data)) {
            show_json(0,$validate->getError());
        }
        $article = db('article')->field('id')->where(['id' => (int)$_GPC['id']])->find();
        if (!$article) {
            show_json(0,'收藏的文章不存在！');
        }

        $article_record = db('article_record')->field('id,disable')->where(['article_id' => $article['id'],'type' => $data['type']])->find();

        if (($article_record)){
            if ($article_record['disable'] == $data['disable']) show_json(0,'操作重复');
            is_error(db('article_record')->where(['id' => $article_record['id']])->update(['disable' => $data['disable'],'updated_time' => time(0)]),'失败');
        } else {
            is_error(db('article_record')->insert(['uniacid' => $_W['uniacid'],'openid' => $_W['openid'],'article_id' => $article['id'], 'type' => $data['type'], 'disable' => $data['disable'],'created_time' => time()]), '失败');
        }
         //更新文章点赞收藏数量
        $fnc = $_GPC['disable'] == 0?'setInc':'setDec';
        $field = $_GPC['type'] == 1?'good_num':'collection_num';
        db('article')->where(['id' => $article['id']])->$fnc($field);

        show_json(1,'成功');
    }

    /**
     * 取出评论内容
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function comment_list()
    {

        global $_W;
        global $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $article_id = $_GPC['id'];

        $data = db('comment a')->where(['a.article_id' => $article_id])->where('a.nickname','<>',' ')->field('a.*,b.content as content2')
            ->leftJoin('comment b','a.parent_id = b.id')
            ->order('a.created_time desc')->limit(($pindex -1) * $psize,$psize)->select();

        $data = array_combine(array_column($data,'id'),$data);
        $ids = array_keys($data);

        $childs = db('comment a')->field('a.*,c.content as content2,b.pid')
            ->leftJoin('yanyu_wq_comment_child b','b.mid = a.id')->where('b.pid', 'in',$ids)->where('a.nickname','<>',' ')
            ->leftJoin('comment c','a.parent_id = c.id')

            ->order('created_time desc')->select();
        //组装数据结构
        foreach ($childs as $key => $item)
        {
            $item['chiled'] = [];
            if(array_key_exists($item['pid'],$data)){
                $data[$item['pid']]['child'][] = $childs[$key];
            }
        }
        show_json(1,['list' => $data]);
    }

    /**
     * 评论
     */
    public function comment()
    {
        global $_W;
        global $_GPC;

        $pid = (int)$_GPC['pid'];

        $data = [
            'uniacid' => 2,
            'openid' => 1,
            'parent_id' => $pid,
            'content' => '应该很好吧！',
            'created_time' => time(),
            'status' => 1,
        ];

        $id = db('yanyu_wq_comment')->insertGetId($data);
        if($pid){
            db::execute("INSERT INTO yanyu_wq_comment_child (mid,pid) SELECT {$id} as mid,pid from yanyu_wq_comment_child a where mid = :mid UNION ALL SELECT {$id},{$pid}",['mid' => $pid]);
        }

        show_json(1,'成功');

    }

    /**
     * 删除评论
     */
    public function comment_delete()
    {
        global $_W;
        global $_GPC;
        $did = $_GPC['id'];

        is_error(db('comment')->where(['id' => $did,'openid' => $_W['openid']])->delete(),'失败');
        db('comment_child')->where(['mid' => $did])->whereOr(['pid' => $did])->delete();

        show_json(1,'成功');
    }

}