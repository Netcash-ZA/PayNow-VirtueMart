Sage Pay Now VirtueMart Payment Gateway Module
==============================================

Revision 1.0.1

Introduction
------------
Sage Pay South Africa's Pay Now third party gateway integration for Joomla VirtueMart

Installation Instructions
-------------------------
Download the files from GitHub to a temporary location:
* https://github.com/SagePay/PayNow-VirtueMart/archive/master.zip

The ZIP file contains all the source code, but the only file that you will require for VirtueMart is in the root folder and it's called:

* mod-virtuemart_1_0.zip

Note the location of this file.

Configuration
-------------

Prerequisites:

You will need:
* Sage Pay Now login credentials
* Sage Pay Now Service key
* OpenCart admin login credentials

A. Sage Pay Now Gateway Server Configuration Steps:

1. Log into your Sage Pay Now Gateway Server configuration page:
	https://merchant.sagepay.co.za/SiteLogin.aspx
2. Type in your Sage Pay Username, Password, and PIN
2. Click on Account Profile
3. Click Sage Connect
4. Click on Pay Now
5. Click "Active:"
6. Type in your Email address
7. Click "Allow credit card payments:"

8. The Accept and Decline URLs should both be:
	http://virtuemart_installation/index.php

9. It is highly recommended that you "Make test mode active:" while you are still testing your site.

B. VirueMart Steps:

1. Log into Joomla as admin
2. Click on Extensions / Extension Manager
3. Click "Choose File" to select the file you download from Github (mod-virtuemart_1_0.zip)
4. Click "Upload & Install"
5. While still in the Extension Manager, click "Manage"
6. Find "Sage Pay Now" which should return "VM Payment - Sage Pay Now"
7. Click on the checkbox to select the Sage Pay Now module and then click "Enable"
8. Navigate to VirtueMart / Payment Method
9. Click Sage Pay Now and then look for the "Configuration" tab at the top right of the page.
10. Enter your Sage Pay Now Service Key here:
11. Click Save & Close

You are now ready to transact. Remember to turn of "Make test mode active:" when you are ready for production.

Revision History
----------------

* 10 May 2014/1.0.1 Improved documentation
* 09 Mar 2014/1.0.0 First version

Tested with Joomla 2.5.19 and VirtueMart 2.6.0

Demo Site
---------
There is a demo site if you want to see Joomla VirtueMart and the Sage Pay Now gateway in action:
http://virtuemart.gatewaymodules.com

Feedback, issues & feature Requests
-----------------------------------
If you have any feedback please contact Sage Pay South Africa or log an issue on GitHub
