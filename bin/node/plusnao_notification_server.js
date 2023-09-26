// ライブラリ
var io
  , fs = require("fs")
  , opts = require('opts')
  , url  = require('url')
  , queryString = require('querystring');


/**
 * 引数設定
 */
opts.parse([
  {
    'short': 'e',
    'long': 'env',
    'description': 'set environment (test | prod)',
    'value': true,
    'required': false
  }
]);

// アプリケーション設定 読み込み
var configAll = require('./config.json');
var config;
var env = opts.get('env') || 'test';
if (env == 'prod') {
    config = configAll.prod;
} else {
    config = configAll.test;
}

var server = require("http").createServer(function(req, res) {

    // TODO BASIC認証 追加

    res.writeHead(200, {"Content-Type":"text/html"});
    // var output = fs.readFileSync("./index.html", "utf-8");
    var output = '';
    res.end(output);

    var requestData;
    var body='';

    // POSTから publish 処理
    if(req.method == 'POST') {
      req.on('data', function (data) {
        body += data;
      });
    } else {
      requestData = url.parse(req.url, true).query;
    }

    req.on('end',function(){
      if (body) { // POSTの場合
        requestData = queryString.parse(body);
      }

      if (requestData.log || requestData.notify) {
        // FIXME 垂れ流し。非常に危険といえば危険。 できるだけ早めに治す
        if (io && io.sockets) {
          io.sockets.emit("publish", requestData);
        }
      }
    });

}).listen(config.port);

// websocket サーバ
io = require("socket.io").listen(server, {
  path: '/notify/socket.io',
  // 本番環境に合わせて設定。開発環境ではシンボリックリンクで対応
  key : fs.readFileSync('/etc/letsencrypt/live/starlight.plusnao.co.jp/privkey.pem').toString(), // /etc/ssl/private/server.key
  cert: fs.readFileSync('/etc/letsencrypt/live/starlight.plusnao.co.jp/cert.pem').toString(), // /etc/ssl/private/server.crt.ONLY
  ca: fs.readFileSync('/etc/letsencrypt/live/starlight.plusnao.co.jp/chain.pem').toString() // /etc/ssl/CA/cacert.pem
});

// ユーザ管理ハッシュ
var userHash = {};

// socket.io イベント定義
io.sockets.on("connection", function (socket) {

  // 特に今は不要
  socket.on("connected", function (name) {
    userHash[socket.id] = name;
  });

  // メッセージ送信カスタムイベント
  socket.on("publish", function (data) {
    // console.log(data);
    io.sockets.emit("publish", data);
  });

  // 特に今は不要
  socket.on("disconnect", function () {
    if (userHash[socket.id]) {
      delete userHash[socket.id];
      // io.sockets.emit("publish", { mode: 'disconnect', name: name});
    }
  });
});

