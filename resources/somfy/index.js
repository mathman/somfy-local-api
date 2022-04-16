const bonjour = require('bonjour')()
const express = require('express')
const axios = require('axios');
const https = require('https');
const fs = require('fs');
const qs = require('qs');
const npid = require('npid');
const passport = require('passport');
const Strategy = require('passport-http-bearer').Strategy;

const args = process.argv.slice(2);
const port = args[0];
const apiKey = args[1];

let webServer;
let intervalId;

let records = [
    { id: 1, username: 'user', token: apiKey, displayName: 'user', emails: [ { value: 'email@email.com' } ] }
];

var browser = bonjour.find({type: 'kizboxdev'}, function (service) {
    clearInterval(intervalId)
    browser.stop()
})

const interval = 1000;
intervalId = setInterval(function() {
    browser.update()
}, interval);

(async () => {
	try {
        var pid = npid.create(args[2]);
        pid.removeOnExit();
    } catch (err) {
        console.log(err);
        process.exit(1);
    }
	
	passport.use(new Strategy(
		function(token, cb) {
			for (var i = 0, len = records.length; i < len; i++) {
				var record = records[i];
				if (record.token === token) {
					return cb(null, record);
				}
			}
			return cb(null, false);
		}
	));
	
    const app = express();

    app.get('/queryServices', 
		passport.authenticate('bearer', { session: false }), 
        async (req, res) => {

            res.setHeader('Content-Type', 'application/json');
			res.end(JSON.stringify(browser.services));
        }
	)
    .get('/generateToken', 
		passport.authenticate('bearer', { session: false }), 
		async (req, res) => {
            
            res.setHeader('Content-Type', 'application/json');

            var dataLogin = qs.stringify({
                'userId': req['query']['login'],
                'userPassword': req['query']['password']
            });
            var configLogin = {
                method: 'post',
                url: 'https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI/login',
                headers: { 
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                data : dataLogin
            };
            
            var responseLogin = await axios(configLogin)
            if (responseLogin.data['success'] !== undefined && responseLogin.data['success'] === true) {
                var jsession = responseLogin.headers['set-cookie'][0].split(';')

                var configGenerate = {
                    method: 'get',
                    url: 'https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI/config/' + req['query']['pin'] + '/local/tokens/generate',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Cookie': jsession[0]
                    }
                };
                var responseGenrate = await axios(configGenerate)
                if (responseGenrate.data['token'] !== undefined) {
                    console.log(responseGenrate.data['token']);
                    var dataActivate = JSON.stringify({
                        "label": "Jeedom token",
                        "token": responseGenrate.data['token'],
                        "scope": "devmode"
                    });
                    var configActivate = {
                        method: 'post',
                        url: 'https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI/config/' + req['query']['pin'] + '/local/tokens',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Cookie': jsession[0]
                        },
                        data : dataActivate
                    };
                    var responseActivate = await axios(configActivate)
                    if (responseActivate.data['requestId'] !== undefined) {
                        res.end(JSON.stringify({
                            "success": true,
                            "token": responseGenrate.data['token'],
                            "requestId": responseActivate.data['requestId']
                        }));
                    }
                    else {
                        res.end(JSON.stringify({
                            "success": false,
                        }));
                    }
                }
                else {
                    res.end(JSON.stringify({
                        "success": false,
                    }));
                }
            }
            else {
                res.end(JSON.stringify({
                    "success": false
                }));
            }
		}
	)
    .get('/setup/gateways', 
		passport.authenticate('bearer', { session: false }), 
		async (req, res) => {
            
            const agent = new https.Agent({
                //ca: fs.readFileSync('overkiz-root-ca-2048.crt')
                rejectUnauthorized: false
            });

            var config = {
                method: 'get',
                url: 'https://' + req['query']['host'] + ':' + req['query']['port'] + '/enduser-mobile-web/1/enduserAPI/setup/gateways',
                headers: {
                  'Authorization': 'Bearer ' + req['query']['token']
                },
                httpsAgent : agent,
            };

            var response = await axios(config)

			res.setHeader('Content-Type', 'application/json');
			res.end(JSON.stringify(response.data));
		}
	)
    .get('/setup', 
		passport.authenticate('bearer', { session: false }), 
		async (req, res) => {
            
            const agent = new https.Agent({
                //ca: fs.readFileSync('overkiz-root-ca-2048.crt')
                rejectUnauthorized: false
            });

            var config = {
                method: 'get',
                url: 'https://' + req['query']['host'] + ':' + req['query']['port'] + '/enduser-mobile-web/1/enduserAPI/setup',
                headers: {
                  'Authorization': 'Bearer ' + req['query']['token']
                },
                httpsAgent : agent,
            };

            var response = await axios(config)

			res.setHeader('Content-Type', 'application/json');
			res.end(JSON.stringify(response.data));
		}
	)
    .get('/setup/devices', 
		passport.authenticate('bearer', { session: false }), 
		async (req, res) => {
            
            const agent = new https.Agent({
                //ca: fs.readFileSync('overkiz-root-ca-2048.crt')
                rejectUnauthorized: false
            });

            var url = '';
            if (req['query']['deviceURL'] !== undefined) {
                url = 'https://' + req['query']['host'] + ':' + req['query']['port'] + '/enduser-mobile-web/1/enduserAPI/setup/devices/' + encodeURIComponent(req['query']['deviceURL'])
            }
            else {
                url = 'https://' + req['query']['host'] + ':' + req['query']['port'] + '/enduser-mobile-web/1/enduserAPI/setup/devices'
            }
            var config = {
                method: 'get',
                url: url,
                headers: {
                  'Authorization': 'Bearer ' + req['query']['token']
                },
                httpsAgent : agent,
            };

            var response = await axios(config)

			res.setHeader('Content-Type', 'application/json');
			res.end(JSON.stringify(response.data));
		}
	)
    .get('/setup/devices/states', 
		passport.authenticate('bearer', { session: false }), 
		async (req, res) => {
            
            res.setHeader('Content-Type', 'application/json');
            const agent = new https.Agent({
                //ca: fs.readFileSync('overkiz-root-ca-2048.crt')
                rejectUnauthorized: false
            });

            if (req['query']['deviceURL'] !== undefined) {
                var config = {
                    method: 'get',
                    url: 'https://' + req['query']['host'] + ':' + req['query']['port'] + '/enduser-mobile-web/1/enduserAPI/setup/devices/' + encodeURIComponent(req['query']['deviceURL']) + '/states',
                    headers: {
                    'Authorization': 'Bearer ' + req['query']['token']
                    },
                    httpsAgent : agent,
                };

                var response = await axios(config)
			    res.end(JSON.stringify(response.data));
            }
            else {
                res.end(JSON.stringify({
                    "success": false,
                }));
            }
		}
	)
	.get('/stop', 
		passport.authenticate('bearer', { session: false }), 
		function(req, res) {
			process.exit(0);
		}
	)
    .use(function(req, res, next){
        res.setHeader('Content-Type', 'text/plain');
        res.status(404).send('Page introuvable !');
    });

    webServer = app.listen(port, function () {
        
        console.log("Api started on port " + port);
    });
})();

process.on("SIGINT", async () => {
    
    if (webServer) {
        
        webServer.close(() => {
            
            console.log('Http server closed.');
        });
    }
    process.removeAllListeners("SIGINT");
});