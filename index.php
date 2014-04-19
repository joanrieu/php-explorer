<?php

$config = new StdClass;
$config->showself = false;
$config->showhidden = false;
// AUTO PASSWORD CONFIG

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

?><!doctype html>
<html>
    <head>

        <meta charset="utf8">
        <title>Explorer</title>

        <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Source+Code+Pro:n,b">
        <style>
            body {
                background-color: #222;
                color: #ccc;
                font-family: 'Source Code Pro';
                margin: 0;
                padding: 0;
            }
            #explorer {
                max-width: 80%;
                margin: auto;
            }
            #path {
                padding: 10pt;
                border: 1px solid #333;
                border-top: none;
                font-weight: bold;
            }
            #files {
                border-left: 1px solid #333;
            }
            #files a {
                text-decoration: none;
            }
            #files a.directory {
                color: #fcc;
            }
            #files a.file {
                color: #cfc;
            }
        </style>

    </head>
    <body>

        <div id="explorer">
            <div id="path">
            </div>
            <ul id="files">
            </ul>
        </div>

        <script src="http://code.jquery.com/jquery.min.js"></script>
        <script>
            var Explorer = new function() {

                var self = this;
                self.password = window.prompt('Explorer password?');
                self.html = {
                    path: $('#path'),
                    files: $('#files'),
                };

                self.update = function() {
                    $.post(
                        '',
                        {
                            password: self.password,
                            directory: self.directory,
                        },
                        function(res) {
                            self.html.files.empty();
                            self.directory = res.directory;
                            self.html.path.text(self.directory);
                            for (name in res.listing) {
                                var path = self.directory + '/' + name;
                                var mime = res.listing[name];
                                var li = $('<li></li>');
                                li.css(
                                    'list-style-image',
                                    'url(//mimeicon.herokuapp.com/' + mime + ')'
                                );
                                var a = $('<a></a>');
                                a.attr('href', path);
                                a.text(name);
                                if (mime === 'directory') {
                                    a.addClass('directory');
                                    a.data('path', path);
                                    a.click(function() {
                                        var path = $(this).data('path');
                                        self.directory = path;
                                        self.update();
                                        return false;
                                    });
                                } else {
                                    a.addClass('file');
                                }
                                li.append(a);
                                self.html.files.append(li);
                            }
                        }
                    );
                };

            };

            Explorer.update();
        </script>

    </body>
</html>
<?php

} else {

    $request = new StdClass;
    $request->password = $_POST['password'];
    $request->directory = @$_POST['directory'];

    $auth = false;

    if (isset($config->password64)) {
        $auth = password_verify($request->password, base64_decode($config->password64));
    } else {
        $password = password_hash($request->password, PASSWORD_DEFAULT);
        file_put_contents(
            __FILE__,
            str_replace(
                '// ' . 'AUTO PASSWORD CONFIG',
                '$config->password64 = \'' . base64_encode($password) . '\';',
                file_get_contents(__FILE__)
            )
        );
        $auth = true;
    }

    if ($auth) {
        header('Content-type: text/json');
    } else {
        header('HTTP/1.1 403 Forbidden');
        die;
    }

    $listing = [];

    if (!isset($request->directory))
        $request->directory = '.';

    foreach (scandir($request->directory) as $name) {
        $path = $request->directory . '/' . $name;
        $show1 = $config->showself || realpath($name) !== realpath(__FILE__);
        $show2 = $config->showhidden || $name === '.' || $name === '..' || $name[0] !== '.';
        if ($show1 && $show2)
            $listing[$name] = mime_content_type($path);
    }

    die(json_encode([
        'directory' => realpath($request->directory),
        'listing' => $listing,
    ]));

}
