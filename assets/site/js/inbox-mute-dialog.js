(function () {
    'use strict';
    var active = null;
    function choose(returnFocus) {
        if (active) return Promise.resolve(null);
        return new Promise(function (resolve) {
            var dialog=document.createElement('dialog'); dialog.className='inbox-mute-dialog';
            dialog.setAttribute('aria-labelledby','inbox-mute-title');
            dialog.innerHTML='<header>'
                +'<h2 id="inbox-mute-title"></h2><button type="button" data-close aria-label="Đóng">×</button></header>'
                +'<form><fieldset></fieldset><p>Cửa sổ chat vẫn mở và bạn sẽ không nhận được thông báo đẩy trên thiết bị.</p>'
                +'<footer><button type="button" data-cancel>Hủy</button><button type="submit" data-next>Tắt thông báo</button></footer></form>';
            document.body.append(dialog); active=dialog;
            var form=dialog.querySelector('form'),fields=dialog.querySelector('fieldset');
            var title=dialog.querySelector('h2');
            var duration='900',finished=false;
            var times=[['900','Trong 15 phút'],['3600','Trong 1 giờ'],['28800','Trong 8 giờ'],['86400','Trong 24 giờ'],['0','Đến khi tôi bật lại']];
            function render(focus) {
                title.textContent='Tắt thông báo về cuộc trò chuyện';
                fields.replaceChildren();
                var legend=document.createElement('legend'); legend.className='inbox-mute-legend';
                legend.textContent='Chọn thời hạn'; fields.append(legend);
                times.forEach(function (option) {
                    var label=document.createElement('label'),input=document.createElement('input'),text=document.createElement('span');
                    input.type='radio'; input.name='mute_duration'; input.value=option[0];
                    input.checked=option[0]===duration; text.textContent=option[1];
                    input.addEventListener('change',function () { duration=input.value; });
                    label.append(input,text);fields.append(label);
                });
                if(focus)fields.querySelector('input:checked').focus();
            }
            function finish(value) {
                if(finished)return;finished=true;
                if(dialog.open)dialog.close();dialog.remove();active=null;
                if(returnFocus && returnFocus.isConnected)returnFocus.focus({preventScroll:true});
                resolve(value);
            }
            form.addEventListener('submit',function (event) {
                event.preventDefault();
                finish({scope:'both',duration:duration});
            });
            dialog.querySelector('[data-close]').onclick=function () {finish(null);};
            dialog.querySelector('[data-cancel]').onclick=function () {finish(null);};
            dialog.addEventListener('cancel',function (event) {event.preventDefault();finish(null);});
            dialog.addEventListener('close',function () {finish(null);});
            render(false);
            if(typeof dialog.showModal!=='function'){finish(null);return;}
            dialog.showModal();fields.querySelector('input:checked').focus();
        });
    }
    window.InboxMuteDialog={choose:choose};
})();
