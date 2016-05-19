var express = require('express')
var app = express();
var mysql = require('mysql');

//MySQL
var connection = mysql.createConnection({
    host     : 'localhost',
    user     : 'root',
    password : '',
    database : ''
});

var saveNotification = function(req, res) {

    if(!req.query.notificationId) { //Error
        res.send('Error: notificationId is mandatory');
    } else { //Save

        //Data
        var notification  = {
            notificationId: req.query.notificationId,
            deviceId: req.query.deviceId,
            userId: req.query.userId
        };

        //Query
        var insertQuery = 'INSERT INTO notifications SET ? ';
        var updateQuery = 'ON DUPLICATE KEY UPDATE deviceId=coalesce('+mysql.escape(req.query.deviceId)+',deviceId), userId=coalesce('+mysql.escape(req.query.userId) +',userId), updateDate=NOW(), loginDate=NOW()';
        var query = connection.query(insertQuery + updateQuery, notification, function(err, result) {

            if(err) {
                console.log('Error: '+JSON.stringify(err));
                console.log('Error SQL: '+query.sql);
                res.status(500).json(err);

            } else {
                res.status(200).json(notification);
            }

        });
    }
};

//Routes
app.get('/', function (req, res) {
    res.send('APIs are live')
});

app.get('/api/notifications', function (req, res) {
    console.log('Processing notification registration..');
    saveNotification(req, res);
});

var server = app.listen(3000, function () {

    var host = server.address().address;
    var port = server.address().port;

    console.log('APIs are live at http://%s:%s', host, port);

    connection.connect(function(err){
        if(err) {
            console.log('Error connecting database: '+JSON.stringify(err));
        } else {
            console.log('Database is connected..');
        }
    });

});