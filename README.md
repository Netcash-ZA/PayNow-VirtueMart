Netcash Pay Now VirtueMart Payment Gateway Module
==============================================

Revision 2.0.0

Introduction
------------
Netcash South Africa's Pay Now third party gateway integration for Joomla VirtueMart

Installation Instructions
-------------------------
Download the files from GitHub to a temporary location:
* https://github.com/Netcash-ZA/PayNow-VirtueMart/archive/master.zip

The ZIP file contains all the source code, but the only file that you will require for VirtueMart is in the root folder and it's called:

* mod-virtuemart_2_0.zip

Note the location of this file.

Configuration
-------------

Prerequisites:

You will need:
* Netcash account
* Pay Now service activated
* Netcash account login credentials (with the appropriate permissions setup)
* Netcash - Pay Now Service key
* Cart admin login credentials

A. Netcash Account Configuration Steps:
1. Log into your Netcash account:
	https://merchant.netcash.co.za/SiteLogin.aspx
2. Type in your Username, Password, and PIN
2. Click on ACCOUNT PROFILE on the top menu
3. Select NETCONNECTOR from tghe left side menu
4. Click on PAY NOW from the subsection
5. ACTIVATE the Pay Now service
6. Type in your EMAIL address
7. It is highly advisable to activate test mode & ignore errors while testing
8. Select the PAYMENT OPTIONS required (only the options selected will be displayed to the end user)
9. Remember to remove the "Make Test Mode Active" indicator to accept live payments

* For immediate assistance contact Netcash on 0861 338 338

10. Click SAVE and COPY your Pay Now Service Key

11. The Accept and Decline URLs should both be:
	http://virtuemart_installation/index.php

12. The Notify and Redirect URLs should both be:
	http://virtuemart_installation/plugins/vmpayment/paynow/paynow_callback.php

13. It is highly recommended that you "Make test mode active:" while you are still testing your site.

B. VirueMart Steps:

1. Log into Joomla as admin
2. Click on Extensions / Extension Manager
3. Click "Choose File" to select the file you download from Github (E.g., mod-virtuemart.zip)
4. Click "Upload & Install"
5. While still in the Extension Manager, click "Manage"
6. Find "Netcash Pay Now" which should return "VM Payment - Netcash Pay Now"
7. Click on the checkbox to select the Netcash Pay Now module and then click "Enable"
8. Navigate to VirtueMart / Payment Method
9. Click Netcash Pay Now and then look for the "Configuration" tab at the top right of the page.
10. Enter your Netcash Pay Now Service Key here:
11. Click Save & Close

B2. Alternatively,

1. Upload the files via FTP to the "plugins/vmpayment/paynow" folder
2. Then, go to Extensions > Discover and click "Discover" in to top right
3. A list should appear, look for "Netcash Pay Now" and select it and click "install"
5. Navigate to VirtueMart / Payment Method
6. Click Netcash Pay Now and then look for the "Configuration" tab at the top right of the page.
7. Enter your Netcash Pay Now Service Key here:
8. Click Save & Close


You are now ready to transact. Remember to turn of "Make test mode active:" when you are ready for production.


Feedback, issues & feature requests
-----------------------------------
If you have any feedback please contact Netcash South Africa or log an issue on GitHub
