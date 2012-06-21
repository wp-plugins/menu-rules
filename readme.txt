=== Menu Rules ===
Contributors: phill_brown
Donate link: http://pbweb.co.uk/donate
Tags: menus, menu, context, rules, parent menu, active menu
Requires at least: 3.2
Tested up to: 3.4
Stable tag: 1.1

An extension of the menu system with context-based rules and a flexible framework to write your own.

== Description ==

In WordPress there's no way to apply context to the menu system. Menu Rules solves this problem and gives you a framework to write your own menu extensions.

= Example usage =

You have an e-commerce website that has a custom post type called 'products'. You have a page that lists products which is listed in your main menu. A user visits the page and the menu item becomes 'active'. You click through to a product and the menu item loses its active state. This is how to fix it with menu rules:

1. [Install](http://wordpress.org/extend/plugins/menu-rules/installation/) the Menu Rules plugin
1. Add a menu rule
1. Give it a meaningful name in the title field. This is just for administration purposes
1. In the conditions field enter `is_singular( 'product' )`
1. Choose *Emulate current page as a child but do not create a menu item.* as the menu rule
1. Find your products page in the menu dropdown
1. Hit publish

= Extending Menu Rules =

1. Create a class that extends `Menu_Rules_Handler` and includes a `handler` method.
1. Write your custom functionality
1. Register your class using `add_action( 'plugins_loaded', create_function( '', 'Menu_Rules::register( "Your_Menu_Rule_Class" );' ) );`

Built-in rules are found in `menu-rules/rules/`

= Support =

If you're stuck, ask me for help on [Twitter](http://twitter.com/phill_brown).

== Installation ==

1. Download and unzip the folder from [the WordPress plugins repository](http://wordpress.org/extend/plugins/menu-rules/)
1. Upload the menu-rules folder into to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Open the 'Appearance' menu item and click the 'Menu Rules' link
1. Add a new menu rule and click ok.

== Changelog ==

= 1.1 =
* Added new 'force inactive parent' rule
* Changed behaviour to one rule per item
* Minor enhancements to PB Framework
* Cleaned some unused code