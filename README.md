##deploy - Upload files to remote server from svn folder.

Its a CLI application to upload files from local svn repository to remote server. It supports both <code>FTP</code> and <code>sFTP</code>. 
The current version is only build for <code>SVN Repository</code>. The application will ask for the revision number, once you enter
that it will connect to server. And fetch the file list from the svn revision log. 

Accoding to the svn list, application will start uploading files to the server. 
<code>ADD/MODIFIED</code> file will be uploaded the exact path. If a new file with a new folder, it will automaticaly create folders
and place files there. And if the revision log contains a <code>DELETE</code> file, it will delete from the server.

All your uploads will be logged into a text file with revision number. You can set the path of the log file in the <code>
deploy.ini</code>

##Limitations


1. Tested only in windows machine.
2. Works only with SVN repository. Git will add soon.
3. And works only if the local folder is a checkout from TRUNK folder.


##Requirements

You need to enable all these three things.

1. <code>php-cli</code>      - Enable and test php in console.
    
2. <code>svn</code>          - Install SVN tool and Add the path to <code>CLASSPATH</code>

3. <code>php_ssh2</code>     - Download the file and enable in php.ini . You can download it from here http://downloads.php.net/pierre/ 


##Install

* Download the application ZIP or RAR file and extract it to you SVN local folder. 
* Open deploy.ini file and add you user credentials and other settings.

##Usage


You can follow two methods to run.

1. Running the php file directly from the console.

    <pre>
    php path/to/svn/folder/deploy/deploy.php --revision(-r)
    Example 1   : D:\xmapp\htdocs\myproject\deploy> php deploy.php -r 14550
    Example 2   : D:\xmapp\htdocs\myproject\deploy> php deploy.php -r14550
    </pre>
    
2. Run the application by double clicking the <code>Batch File</code>.

    <pre>
    Enter Revision Number: 14550
    </pre>


##Version

1.0 

* First Release