define(['core', 'tpl'], function (core, tpl) {
    var modal = {};
    modal.initList = function () {
        $('*[data-toggle=delete]').unbind('click').click(function () {
            var item = $(this).closest('.address-item');
            var id = item.data('addressid');
            FoxUI.confirm('删除后无法恢复, 确认要删除吗 ?', function () {
                core.json('member/bankcard/delete', {
                    id: id
                }, function (ret) {
                    if (ret.status == 1) {
                        if (ret.result.defaultid) {
                            $("[data-addressid='" + ret.result.defaultid + "']").find(':radio').prop('checked', true)
                        }
                        item.remove();
                        setTimeout(function () {
                            if ($(".address-item").length <= 0) {
                                $('.content-empty').show()
                            }
                        }, 100);
                        return
                    }
                    FoxUI.toast.show(ret.result.message)
                }, true, true)
            })
        });
        $(document).on('click', '[data-toggle=setdefault]', function () {
            var item = $(this).closest('.address-item');
            var id = item.data('addressid');
            core.json('member/bankcard/setdefault', {
                id: id
            }, function (ret) {
                if (ret.status == 1) {
                    $('.fui-content').prepend(item);
                    FoxUI.toast.show("设置默认银行卡成功");
                    return
                }
                FoxUI.toast.show(ret.result.message)
            }, true, true)
        })
    };
	modal.verifycode = function () {
        modal.seconds--;
        if (modal.seconds > 0) {
            $('#btnCode').html(modal.seconds + '秒后重发').addClass('disabled').attr('disabled', 'disabled');
            setTimeout(function () {
                modal.verifycode()
            }, 1000)
        } else {
            $('#btnCode').html('获取验证码').removeClass('disabled').removeAttr('disabled')
        }
    };
	modal.initBind = function (params) {
        modal.endtime = params.endtime;
        modal.imgcode = params.imgcode;
        if (modal.endtime > 0) {
            modal.seconds = modal.endtime;
            modal.verifycode()
        }
        $('#btnCode').click(function () {
            if ($('#btnCode').hasClass('disabled')) {
                return
            }
            if (!$.trim($('#verifycode2').val()) && modal.imgcode == 1) {
                FoxUI.toast.show('请输入图形验证码');
                return
            }
            modal.seconds = 60;
            core.json('member/bankcard/verifycode', {
                temp: 'sms_bind',
                imgcode: $.trim($('#verifycode2').val()) || 0
            }, function (ret) {
                if (ret.status != 1) {
                    FoxUI.toast.show(ret.result.message);
                    $('#btnCode').html('获取验证码').removeClass('disabled').removeAttr('disabled')
                }
                if (ret.status == 1) {
                    modal.verifycode()
                }
            }, false, true)
        });
        
        $("#btnCode2").click(function () {
            $(this).prop('src', '../web/index.php?c=utility&a=code&r=' + Math.round(new Date().getTime()));
            return false
        })
    };
    modal.initChange = function (params) {
        modal.endtime = params.endtime;
        modal.imgcode = params.imgcode;
        if (modal.endtime > 0) {
            modal.seconds = modal.endtime;
            modal.verifycode()
        }
        $('#btnCode').click(function () {
            if ($('#btnCode').hasClass('disabled')) {
                return
            }
            if (!$('#mobile').isMobile()) {
                FoxUI.toast.show('请输入11位手机号码');
                return
            }
            if (!$.trim($('#verifycode2').val()) && modal.imgcode == 1) {
                FoxUI.toast.show('请输入图形验证码');
                return
            }
            modal.seconds = 60;
            core.json('member/bankcard/verifycode', {
                temp: 'sms_changepwd',
                imgcode: $.trim($('#verifycode2').val()) || 0
            }, function (ret) {
                if (ret.status != 1) {
                    FoxUI.toast.show(ret.result.message);
                    $('#btnCode').html('获取验证码').removeClass('disabled').removeAttr('disabled')
                }
                if (ret.status == 1) {
                    modal.verifycode()
                }
            }, false, true)
        });
        
        $("#btnCode2").click(function () {
            $(this).prop('src', '../web/index.php?c=utility&a=code&r=' + Math.round(new Date().getTime()));
            return false
        })
    };
    modal.initPost = function (params) {
        var reqParams = ['foxui.picker'];
        
        $(document).on('click', '#btn-submit', function () {
            if ($(this).attr('submit')) {
                return
            }
            if ($('#realname').isEmpty()) {
                FoxUI.toast.show("请填写持卡人");
                return
            }
            if ($('#cardnumber').isEmpty()) {
                FoxUI.toast.show("请填写银行卡号");
                return
            }
			if (!$.trim($('#verifycode2').val()) && modal.imgcode == 1) {
                FoxUI.toast.show('请输入图形验证码');
                return
            }
            if (!$('#verifycode').isInt() || $('#verifycode').len() != 5) {
                FoxUI.toast.show('请输入5位数字验证码');
                return
            }
            $('#btn-submit').html('正在处理...').attr('submit', 1);
            window.editAddressData = {
                realname: $('#realname').val(),
                bankid: $('#bankid').val(),
				verifycode: $('#verifycode').val(),
                cardnumber: $('#cardnumber').val()
            };
            core.json('member/bankcard/submit', {
                bankcarddata: window.editAddressData
            }, function (json) {
                $('#btn-submit').html('保存').removeAttr('submit');
                if (json.status == 1) {
                    FoxUI.toast.show('保存成功!');
                    history.back();
					location.reload();
                } else {
                    FoxUI.toast.show(json.result.message)
                }
            }, true, true)
        })
    };
   
    return modal
});