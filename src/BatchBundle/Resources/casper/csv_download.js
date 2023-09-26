var casper = require("casper").create({
  verbose: true,
  logLevel: 'debug'
});

var system = require('system');
var fs   = require('fs');
var utils = require('utils');

// ログインURL, アカウント, パスワード
var loginUrl  = system.env.NE_LOGIN_URL;
var account = system.env.NE_LOGIN_ACCOUNT;
var password = system.env.NE_LOGIN_PASSWORD;

console.log(loginUrl, account, password);

var capturePath = '/home/hirai/working/ne_api/web/';

// vars
var accountHostName = null;
var downloadFiles = [];

// 送信する User-Agent
casper.userAgent("Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36");

casper.on('http.status.404', function(resource) {
  this.log('Hey, this one is 404: ' + resource.url, 'warning');
});

casper.on('resource.requested', function(request) {
  // utils.dump(request);
});

casper.on('resource.received', function(resource) {

  if (
       resource.status == 200
    && resource.contentType == "application/octet-stream"
    && resource.url.indexOf('Userinspection2/oddlexe') != -1
  ) {

    var fileName = '';
    var headers = resource.headers;
    this.each(headers, function(self, ele) {
      if (ele.name == "Content-Disposition") {
        fileName = ele.value;
        fileName = fileName.replace(/^attachment; filename="([^"]+)"$/, "$1");

        self.echo(fileName);
      }
    });

    this.echo("ダウンロード");
  }

});


/*
casper.onError(function(e) {
  this.echo('エラー');
  console.log('error');
  this.echo(e);
});
*/

// 指定した URL へ遷移する
casper.start(loginUrl, function() {

  var scope = this;
  scope.viewport(1280, 1000);

  // ログインページ
  scope.then(function() {

    this.echo('ログイン画面');
    this.capture(capturePath + '01.png');

    this.fillSelectors('form[action="/users/sign_in/"]', {
      '#user_login_code': account
      , '#user_password': password
    }, true); // submit

  }).waitForUrl('https://base.next-engine.org/', function() {

    this.echo('ログイン後画面');
    this.echo(this.getCurrentUrl());

    this.capture(capturePath + '03.png');

  }).thenOpen('https://base.next-engine.org/apps/launch/?id=52', function() {

    this.echo('メイン画面 アクセス中');

    // メイン画面
  }).waitForUrl(/.next-engine.org\/Usertop$/, null, function() {
    this.echo('メイン画面 遷移失敗');

    // TODO エラー処理
  }, 120000).then(function() {

    this.echo('メイン画面');
    var url = this.getCurrentUrl();
    this.echo(url);
    this.capture(capturePath + '04.png');

    var match;
    if (match = url.match(/^(.*\.next-engine\.org\/)/)) {
      accountHostName = match[1];

      this.open(accountHostName + 'Userinspection2', function() {
        this.echo('CSVダウンロード画面 アクセス中');
        this.echo(accountHostName + 'Userinspection2');

      });
    }
  // CSVダウンロード画面
  }).waitForUrl(/Userinspection2$/, null, null, 120000).then(function() {

    var local = this;
    this.echo('CSVダウンロード画面');

    var url = this.getCurrentUrl();
    this.echo(url);
    this.capture(capturePath + '05.png');

    if (this.exists('select#id')) {
      var optionValue = null;
      this.each(this.getElementsInfo('select#id option'), function(self, ele) {
        if (ele.text.indexOf("【オリジナル】売上集計(日時指定)") != -1) {
          optionValue = ele.attributes.value
        }
      });

      if (optionValue) {
        this.fillSelectors('form[action="/Userinspection2/dl"]', {
          "select#id" : optionValue
        }, false);
      }
      this.echo('オプションを選択しました。');

    } else {
      this.echo('オプションがありません。');

    }
  }).waitForSelector('input#from_cal2_1', function() {

    this.echo('CSVを選択成功');
    this.capture(capturePath + '06.png');

    this.fillSelectors('form[action="/Userinspection2/dl"]', {
        'input#from_cal2_1': "2015-07-1"
      , 'input#to_cal2_1': "2015-10-30"
      , 'select[name="moji_code"]': "SJIS"
    }, false);

    this.click('.form-actions button');

  }).then(function() {

    this.echo('遷移中...');
    this.capture(capturePath + '07.png');

  })
  .waitForSelector('select#file_name', function(){

    this.echo('日付を選択成功');
    this.capture(capturePath + '08.png');

    var eles = this.getElementsInfo('select#file_name option');
    var ele = eles[0];

    var value = ele.attributes.value;

    var params = this.getElementsInfo('form.form-horizontal input[type="hidden"]');
    params.file_name = value;

    utils.dump(params);

    this.download("https://ne48.next-engine.org/Userinspection2/oddlexe", 'test.csv', params);

      /*
    this.fillSelectors('form.form-horizontal', {
      'select#file_name' : value
    }, false);

    this.click('.form-actions button:first-child');
*/

    /*
    this.each(this.getElementsInfo('select#file_name option'), function(self, ele) {

      var value = ele.attributes.value;

      self.echo(ele);
      self.fillSelectors('form.form-horizontal', {
        'select#file_name' : value
      }, false);

      self.click('.form-actions button:first-child');
    });
    */

  }, null, 60000)
  ;

}).then(function() {
  this.echo('POST 送信した！');


});


// 処理を開始する
casper.run();
