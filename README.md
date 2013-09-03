TreeOrder
=============

A CakePHP behaviour that maintains a 'path' field that is used for ordering records in a tree based table.

What it does
------------

Ever tried to order data from a tree based table? Unless the data is stored in an ordered manner this is really hard. The challenge is that the order instruction applies to a field (or fields) that apply across the whole data set. So if you order by 'title' all the titles beginning with 'a' will be together, followed by the bs and so on. This brings them out of thier hierarical position as the parent/child relationship is ignored.

You can rely on the lft column assuming that the data is neatly stored and maintained (and you can use the Tree->reorder function for that) but in my experience that can add a noticeble performance overhead, especially on larger tables. This is because each update cascades further updates that can get into a deep loop.

If you don't or can't rely on the lft field you could do some complex recursive ordering within each key but even thinking about that makes my eyes bleed.

Instead, I prefer to add and maintain a 'path' field that contains a text value I can rely on for ordering. It also has useful presentational properties.

Updating the 'path' field is less cumbersome than maintaining order by lft because an update only affects 'this' row. Take this really simple example:

lft						rght
1	Finance					6
2		- Accounting		3
4		- Reporting			5
6	Sales					19
7		- Marketing			12
8			- Direct		9
10			- Indirect		11
13		- Promotions		18
14			- Radio			15
16			- TV			17


If I decide to rename Promotions to Advertising and want to keep the tree ordered by the lft field, the new tree looks like this:

lft						rght
1	Finance					6
2		- Accounting		3
4		- Reporting			5
6	Sales					19
[7]		- Advertising		[12]
[8]			- Radio			[9]
[10]		- TV			[11]
[13]	- Marketing			[18]
[14]		- Direct		[15]
[16]		- Indirect		[17]

The lft and rght values in brackets are the ones that are changed. That's a lot of changes for something fairly simple, especially when the only row that really changed was the Prmotions row changing its title to Advertising.

My preferred solution is to add a text field that contains the full path back to the root deliminated in some way. For example:
	Sales > Promotions
	Sales > Promotions > Radio
	Sales > Promotions > TV

...which would become:
	Sales > Adverstising
	Sales > Adverstising > Radio
	Sales > Adverstising > TV

That's three row changes instead of six. So not only is it half as many changes, it's also half as many before and after saves and any other cascading that also has to happen.

So by adding a path field my final tree looks like this:

Finance
Finance > Accounting
Finance > Reporting
Sales
Sales > Advertising
Sales > Advertising > Radio
Sales > Advertising > TV
Sales > Marketing
Sales > Marketing > Direct
Sales > Marketing > Indirect

I can now query the tree however I like and order it by the path field and it will always be ordered correctly. I can also display it to the user so he has an idea of where this row sits in the hierarchy. That's a quick and dirty way to fill a select box too.


How it works
------------

To install and use the Plugin
-----------------------------
* Copy the Plugin into app/Plugin/TreeOrder
* Edit app/Config/bootstrap.php:
	CakePlugin::load(array(
		// your existing plugins...,
		'TreeOrder'
	);

* Attach the behaviour to your tree based model:

	public $actsAs = array(
		...,
		'TreeOrder.TreeOrder'
	);

Add a new field 'path' with a fairly long character length (note: text fields are way too big, can't be indexed or used for ordering and don't perform well) to your tree based table:

	ALTER `table_name`
	ADD COLUMN `path` varchar(2000) DEFAULT NULL AFTER `another_column_name`;


Initialising data
-----------------

The behaviour has a function setAllpaths that can be called from a controller function. It loops through every row in the attached model setting the initial value for the path field.

That's it. The behaviour has a beforeSave function that captures some basic information and sets some triggers that are fired in afterSave. The path field will now stay up to date.