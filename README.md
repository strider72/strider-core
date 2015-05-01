# Strider Core

Strider Core is a framework for use in WordPress plugins. It is not a plugin unto itself. A significant feature is the ability to activate integrated version checking for plugins not hosted on WP-Extend. It also includes many points of minor functionality and polish, such as Settings page links from the Plugins page, and an attribution in the footer of the Settings page.

Strider Core also has an interesting feature in that if multiple plugins are built on it, they will all use whichever is the highest version present. Thus, upgrading one plugin can improve the function of other Strider Core plugins running at the same time. 

*NOTE:* This framework is still in early development, and thus functions may not be entirely stable.  There is no guarantee that later pre-1.0 versions will be backward compatible. *As a public framework, consider it alpha*, folks!

## License
Strider Core is released under a modified GPL2 license.
The modification is as follows:

1. If you make _any_ changes to the code, other than for purposes of private testing, you are required to change all in-code instances 	of the string `strider_core` -- do a search/replace and replace `strider_core` with something unique, e.g. `<yourname>_core`. (Note: not to be confused with `strider-core` with a dash. The dashed string may be left alone; and probably should be.)

2. Do not perform the above replacement using anything containing `strider_core`.  That is, don't call it `strider_core_2` or `my_strider_core` or something.  If you modify it, it isn't Strider Core, so really rename it.  (Yes, something very much like this has happened to me in the past.)

3. If you do not do the above (#1 and #2), then Strider Core MUST NOT BE MODIFIED IN ANY WAY. This is important, because otherwise your modifications could adversely affect plugins or software *other than your own!*

--

If you make modifications and come up with something good, please let me know -- I may add it to the original.  :-)

I would appreciate (but do not require) an attribution that the new script is based upon Strider Core, with URL etc.

Everything within the "strider-core" folder is considered a part of Strider Core.

Trespassers will be violated.

## How Strider Core versioning works:
* Every Strider Core plugin loads its own strider-core.php file.
* The first strider-core.php to be loaded creates the `load_strider_core()` function, and sets it to run on the plugins_loaded hook.
* For each strider-core.php file, the `__FILE__` and version (above) are loaded into `$this_strider_core[]`, and then passed into the `$strider_core_info[]` array.  `$this_strider_core[]` is unset.
* After all plugins are loaded, `load_strider_core()` runs, in which `$strider_core_info[]` is UNset, and the strider-core.php file with the highest version is loaded again
* This time, the `strider_core` class is created.  (also, `$strider_core_info` is recreated, with data for the current strider_core.php file only)
* Finally, the list of Strider Core plugins is looped through and each SC plugin "main" file is loaded.  Those files are the heart of their respective plugins.
 
In the end, you are left with:
* global `$strider_core_info[]`, which == the `$this_strider_core` array from the top of the active core only
* global `$strider_core_plugins[]` -- keys are the plugin basename and `['core file']` the "main" file full path for every active Strider Core plugin
* ...and of course, the strider_core class

## Known Limitations:
* "main" plugin file isn't called until `plugins_loaded` hook.  This is soon after normal plugin load, but some things do happen before that.  Such as...
* activate and deactivate hooks don't work from the "main" plugin files, because those files aren't called until after those hooks are fired.  An uninstall.php file should still work just fine.

* Future compatibility note: If an upgrade is ever significantly non-backwards compatible, I can simply create `load_strider_core_2()` which will only load legacy SC1 code if needed.  The code that hooks `plugins_loaded` will also un-hook the old `load_strider_core()` as needed.

## Known Issues:
* The Version Check routine is not complete.  Fully WP integrated, but lacks the actual code that goes and checks a server for a new version.  You can test by upping the version returned by `filter_set_update_plugins()`
