<div style="background: #eee">
    <table width="600" border="0" align="center" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td>
                <div style="background:#fff">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <thead>
                        <tr>
                            <td valign="middle" style="padding-left:30px;background-color:#415A94;color:#fff;padding:20px 40px;font-size: 21px;">{{$name}}</td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr style="padding:40px 40px 0 40px;display:table-cell">
                            <td style="font-size:24px;line-height:1.5;color:#000;margin-top:40px">支付提醒</td>
                        </tr>
                        <tr>
                            <td style="font-size:14px;color:#333;padding:24px 40px 0 40px">
                                尊敬的用户您好！
                                <br />
                                <br />
                                您有一笔订单尚未完成支付（可能仍在待支付，或已因超时取消）：
                                <br />
                                订单号：{{$trade_no}}
                                <br />
                                金额：¥{{$amount}}
                                <br />
                                <br />
                                若刚才的支付方式无法完成付款，请返回网站重新下单，并在支付选择页面更换其他支付渠道再试。当前可用通道：{{$payment_channels}}，均可选择尝试。
                                <br />
                                <br />
                                如果您已完成支付，请忽略本邮件。
                            </td>
                        </tr>
                        <tr style="padding:40px;display:table-cell">
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tbody>
                        <tr>
                            <td style="padding:20px 40px;font-size:12px;color:#999;line-height:20px;background:#f7f7f7"><a href="{{$order_url}}" style="font-size:15px;color:#ff0000">点击前往订单/支付页面</a></td>
                        </tr>
                        </tbody>
                    </table>
                </div></td>
        </tr>
        </tbody>
    </table>
</div>
