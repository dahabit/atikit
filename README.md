aTikit v1.0
===========
<a href="http://www.core3networks.com">
  <img src="http://my.secrds.com/containers/04239a258eef046714f996913b6956ca/site/images/c3.gif">
</a>

Installation Video http://www.youtube.com/watch_popup?v=VVJ8gR1_EC4&vq=hd1080

aTikit is a ticketing system that includes billing for some of the newest 
merchant providers out there; namely [Stripe] and [Dwolla]. When looking
for a system to help manage my web development projects I realized there
wasn't anything out there that was simple to use, didn't cost much, and
did everything I needed. I developed aTikit (from the phrase: "Did you
put it in 'a ticket'?") to handle all my internal tasks, and be able
to bill clients based on a statement of work. aTikit Comes with the 
following functions: 

Feature List
-------------------
1. Administrative Inteface to create multiple Queues (i.e. billing, sales,
technical support. *Supports Unlimited Queues*
2. Manage Stripe.com Plans, Coupons and Track Payout Schedules
3. Integrate Dwolla support so your customers can pay with Checking and
avoid credit card fees.
4. Unlimited Access Levels with *hide billing* support for agents who
cannot see billing information.
5. SMS Capabilities with [Vitelity]. Send SMS messages to key agents when
a company is set to VIP status. Notifications when tickets are created and
updated.
6. Create Statements of Work (i.e Quotes). Quotes are sent to the customer's 
e-mail address and can accept via an accept link to signify that they
approve the quote and you can begin work.
7. Ticket Tasks with each ticket. In order to keep tickets from getting 
incredibly long, ticket tasks (or subtickets) are created and are only
viewable by the providing company agents. Each task can be assigned
to different agents and notations are provided for each.
8. Customer notations can be made under the history tab only viewable by the 
providing company.
 
 
Future Plans
---------------------------
1. Create Partnerships - With the aTikit API you will be able to establish
a partnership with another company and share tickets when applicable. For 
instance, if you are a designer, and you have a web developer that you work
with on a consistent basis, you can add them as a partner. Once added,
the other company can be added to tickets on a as-needed per-ticket basis.

Integrated Services
---------------------------
*stripe.com* is used for Credit Card Processing. We did not go with any other
providers like authorize.net because you have to keep cards on file. Stripe
is a newer company, and we wanted to go with newer technology than archaic
billing processors. 
*dwolla.com* is used for bank to bank transfers. Dwolla.com is being used 
all over the place and only charges $0.25 per transaction. Which is insane,
so we integrated their API. 
*vitelity.net* - Vitelity is a VOIP/SMS/vFAX provider. They were the best
and easiest choice to go with when dealing with sending SMS.

Requirements
----------------------------
1. PHP 5.4.x - You must have PHP 5.4 installed for aTikit to function. I did
this on purpose. I'm tired of seeing old PHP installations out there; upgrade your stuff.
2. Apache with mod_rewrite - I use .htaccess for my url dispatchers in this code. It started
off as a bad habit, and ended as a bad habit.
3. MySQL and Memcache - Both are required. Install MySQL 5.5. It's not required but
you should be using it anyway.
4. Postfix - If you are using exim, you have no idea what you are doing and need to probably
host this with someone else. Otherwise apt-get install postfix.
5. Debian - If you use centos, I pity the fool. 

How to Install aTikit
--------------------
1. Checkout the Repo
```git clone https://github.com/core3net/atikit.git```
2. Create Database and import shell.sql
3. Modify classes/config.inc.php and change the db configs and namespace prefix (and memcache)
4. Login to the URL you have set up and create an account. The first account you create is 
considered the admin account and providing company.
5. Update logo.png with your Company Logo. This is used for quotes that you send
 to clients. You can specify a different filename in the settings for quotes if needed.
6. Click *ATIKIT ADMIN* at the top of the page. Update the settings in the following order:
 * Access Levels - Create your access levels that you will be using. At least add Administrator 
 * aTikit Users - Edit your admin user, and set your new access level.
 * Queues - Create your queues. The email addresses you use for your queues must support IMAP with
 SSL/TLS
 * General Settings - Update your Stripe Keys, General Settings, Dwolla Keys and Vitelity Keys if
 you are going to be using SMS. 
7. Add a cronjob that runs every minute to poll your queues for new emails. As an Example 
```*/1 * * * * cd /web/sites/atikit/dev.tikit.co; /usr/bin/php poll.php > /dev/null 2>&1```
8. Make sure to create/chmod 777 the INSTALL-DIR/files folder. This is where profile pics and
ticket uploads are stored.

Disclaimer
-------------------
This is my first project that I am releasing to the public. My code uses libraries that I have
developed over the years that help me do menial tasks. If you see some functions in my tools
file that you cannot understand why they are there, they probably aren't for this project. 

Authors
-------------------
Chris Horne http://www.facebook.com/superawesome - chorne@core3networks.com

[stripe]: http://www.stripe.com
[dwolla]: http://www.dwolla.com
[vitelity]: http://www.vitelity.net
