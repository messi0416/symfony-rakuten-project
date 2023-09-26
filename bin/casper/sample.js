var casper = require('casper').create({
    verbose: true,
    logLevel: "debug"
});

var account = 'tKg2nJL7Be';
var password = 'plusnao888';

var userAccount = 'rakuten_naoya';
var userPassword = 'yoshiko';

var captureNum = 0;
function capturePageByNum(self)
{
	captureNum += 1;
	var fileName = "cap" + captureNum.toString() + ".png"
	self.capture(fileName);
}


casper.start('https://glogin.rms.rakuten.co.jp/?sp_id=1', function() {
	this.echo(this.getTitle());
    this.fill('form[action="https://glogin.rms.rakuten.co.jp/"]', { login_id: account, passwd: password }, true);
});

casper.waitForSelector('input[name="user_id"]', function() {
	this.echo(this.getTitle());
    capturePageByNum(this);

    this.fill('form[action="https://glogin.rms.rakuten.co.jp/"]', { user_id: userAccount, user_passwd: userPassword }, true);
});

casper.waitForText('楽天からのお知らせ', function() {
	this.echo(this.getTitle());
    capturePageByNum(this);
    
    this.click('input[type="submit"]');
});

casper.waitForText('楽天市場出店規約・ルール・ガイドラインの遵守のお願い', function() {
	this.echo(this.getTitle());
    capturePageByNum(this);
    
    this.click('input[type="submit"]');
});

casper.waitForUrl('https://mainmenu.rms.rakuten.co.jp/', function() {
	this.echo(this.getTitle());
    capturePageByNum(this);
    
    this.click('a[href="https://mainmenu.rms.rakuten.co.jp/?left_navi=32"]');
});


casper.waitForText('アクセス分析機能一覧', function() {
	this.echo(this.getTitle());
    capturePageByNum(this);
    
    this.click('a[href="https://rdatatool.rms.rakuten.co.jp/access/?menu=pc&evt=RT_P03_01&stat=1"]');
});

casper.waitForSelector('form[name="select_day"]', function() {
	this.echo(this.getTitle());

    // PC 日次データ 選択
    this.click('input[value="pc"]');
    this.click('a[href="javascript:setNewDays()"]');
    
    capturePageByNum(this);

}, null, 5000);

casper.then(function() {
    this.click('input[type="submit"][value="日次データ表示"]');
});

casper.waitForSelector('input[value="ダウンロードする"]', function() {
	this.echo(this.getTitle());
    capturePageByNum(this);
	
});


casper.run();
