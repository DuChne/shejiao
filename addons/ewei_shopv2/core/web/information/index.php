<?php
/**
 * author tanshenxiao
 */

use think\Validate;


class Index_EweiShopV2Page extends  WebPage
{
    /**
     * 能人号
     */
    public function lr_number()
    {
        global $_W;
        global $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $where[] = ['type','=',1];
        //标题模糊查询
        if($_GPC['keyword']){
            $where[] = ['title','like','%'.$_GPC['keyword'].'%'];
        }
        //显示隐藏模糊查询
        if($_GPC['is_pay'] != ''){
            $where[] = ['is_pay','=',$_GPC['is_pay']];
        }
        //审核状态
        if($_GPC['status'] != ''){
            $where[] = ['status','=',$_GPC['status']];
        }

        $list = db('article')->where($where)->limit(($pindex - 1) * $psize,$psize)->order('created_time desc')->select();
        $total = db('article')->where($where)->count('id');
        $pager = pagination2($total, $pindex, $psize);

        include $this->template('',get_defined_vars());
    }

    /**
     * 畅聊号
     */
    public function cl_number()
    {
        global $_W;
        global $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $where[] = ['type','=',2];
        //标题模糊查询
        if($_GPC['keyword']){
            $where[] = ['title','like','%'.$_GPC['keyword'].'%'];
        }
        //显示隐藏模糊查询
        if($_GPC['is_pay']){
            $where[] = ['is_pay','=',$_GPC['is_pay']];
        }
        //审核状态
        if($_GPC['status']){
            $where[] = ['status','=',$_GPC['status']];
        }


        $list = db('article')->where($where)->limit(($pindex - 1) * $psize,$psize)->order('created_time desc')->select();
        $total = db('article')->where($where)->count('id');
        $pager = pagination2($total, $pindex, $psize);


        include $this->template('',get_defined_vars());
    }

    /**
     * 审核
     */
    public function cl_edit()
    {
        global $_GPC;
        global $_W;
        $id= (int)$_GPC['id'];

        $item = db('article')->where(['id' => $id])->find();
        if($_W['ispost']){
            $data = [
                'is_show' => $_GPC['is_show'],
                'status' => $_GPC['status'],
                'reason' => $_GPC['reason'],
            ];

            $validate = new Validate(['is_show|是否显示' => 'require','status|状态' => 'in:2,3']);
            if(!$validate->check($data)){
                show_json(0,$validate->getError());
            }
            if($data['status'] == 3 and !$data['reason']){
                show_json(0,'请填写驳回理由！');
            }

            if($id){
                $data['updated_time'] = time();
                db('article')->where(['id' => $id])->update($data);
                show_json(1,'更新成功');
            }

            show_json(0,'修改失败');

        }

        include $this->template('',get_defined_vars());
    }

    /**
     * 编辑能人号
     */
    public function lr_edit()
    {
        global $_GPC;
        global $_W;
        $id= (int)$_GPC['id'];

        $item = db('article')->where(['id' => $id])->find();
        if($_W['ispost']){
            $data = [
                'is_show' => $_GPC['is_show'],
                'status' => $_GPC['status'],
                'reason' => $_GPC['reason'],
            ];

            $validate = new Validate(['is_show|是否显示' => 'require','status|状态' => 'in:2,3']);
            if(!$validate->check($data)){
                show_json(0,$validate->getError());
            }
            if($data['status'] == 3 and !$data['reason']){
                show_json(0,'请填写驳回理由！');
            }

            if($id){
                $data['updated_time'] = time();
                db('article')->where(['id' => $id])->update($data);
                show_json(1,'更新成功');
            }

            show_json(0,'修改失败');

        }

        include $this->template('',get_defined_vars());
    }

    /**
     * 设置显示还是隐藏
     */
    public function show()
    {
        global $_GPC;
        if(empty($_GPC['ids'])){
            show_json(0,'请选者');
        }
        if(!in_array($_GPC['is_show'],[1,2])){
            show_json(0,'请选择正确的操作');
        }

        if(db('article')->where(['id' => $_GPC['ids']])->update(['is_show' => $_GPC['is_show']])){
            show_json(1,'改变成功');
        }

        show_json(0,'改变失败');

    }

    /**
     * 删除文章
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete()
    {
        global $_GPC;
        if(empty($_GPC['ids'])){
            show_json(0,'请选者');
        }

        if(db('article')->where(['id' => $_GPC['ids']])->delete()){
            show_json(1,'删除成功');
        }

        show_json(0,'删除失败');
    }

    /**
     * 提交审核
     */
    public function examine()
    {
        global $_GPC;
        if(empty($_GPC['ids'])){
            show_json(0,'请选者');
        }

        if($not_data = db('article')->field('title')->where([['id','in', $_GPC['ids']],['status','not in',[0,3]]])->find()){
            show_json(0,$not_data['title'].'不予许提交审核');
        }

        if(db('article')->where(['id' => $_GPC['ids']])->update(['status' => 1])){
            show_json(1,'审核提交成功');
        }

        show_json(0,'审核提交失败');
    }

    /**
     * 文章支付
     */
    public function article_pay()
    {
        global $_GPC;
        global $_W;

        $id= (int)$_GPC['id'];
        $data = db('article')->where(['id' => $id])->find();
        if (2 == $data['is_pay']){
            show_json(0,'该文章已支付');
        }

        if (!in_array($data['type'],[1])) {
            show_json(0,'该文章不支持支付');
        }

        $money = 0.01;
        $article_order = db('article_order')->where(['article_id' => $id,'status' => 1])->find();
        if(!$article_order){
            $article_order = [
                'uniacid' => $_W['uniacid'],
                'merchid' => $_W['merchid'],
                'order_sn' => m('common')->createNO('order', 'ordersn',sqSign($_W['uniacid'],19)),
                'price' => $money,
                'article_id' => $id,
                'created_time' =>time(),
                'updated_time' => time(),
            ];
            if(!db('article_order')->insert($article_order)){
                show_json(0,'订单创建失败');
            }
        }

        //双乾支付开启
        $dn = DIRECTORY_SEPARATOR;
        include_once(IA_ROOT.$dn.'addons'.$dn.'ewei_shopv2'.$dn.'sqpay'.$dn.'toPayHtml.php');

        $returnUrl = $_SERVER['HTTP_REFERER'];
        $sqpay = new SqPay();

        $sqpay->pay($article_order['order_sn'],$article_order['price'],$returnUrl,'pc','文章发布购买');
    }


}