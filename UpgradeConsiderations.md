#summary Things to consider before upgrading.
#labels Phase-Deploy

If you are upgrading from a version prior to 1.1, at least some minor updates to geo mashup tags will be required. See TagRefOneOne for old and new tag formats.

Other considerations all have to do with Geo Mashup customizations. If you haven't created any files for Geo Mashup
(aside from those it comes with), you are safe to upgrade any way you like.

If you have some customizations, keep reading. If something doesn't make sense, you probably don't have to worry about it.

# Upgrading with Customizations #

Starting with WordPress 2.7, you can upgrade plugins automatically from the admin interface
via the "upgrade automatically" and related links. **This method deletes the geo-mashup directory,
and any files you've created there**. Here is a safe upgrade path:

  1. If you are using a customized version of Geo Mashup, please download, install and activate the [Geo Mashup Custom plugin](http://wordpress-geo-mashup.googlecode.com/files/geo-mashup-custom-1.0.zip);
  1. After activation click the link to 'list current custom files' on the Manage Plugins page in WP Admin;
  1. If all of your custom files are listed (e.g. map-style.css, info-window.php, full-post.php, custom.js) then you can now safely perform an automatic upgrade of the Geo Mashup plugin itself to retain your customizations;
  1. If your custom files could not be automatically recovered during activation of the Geo Mashup Custom plugin (e.g. due to server permissions) you will need to move them manually into the /plugins/geo-mashup-custom directory. Once here they will not be overwritten during subsequent upgrades of Geo Mashup itself, which you can now perform automatically.

That's it for upgrading. The rest of the information here relates to options and requirements for customizations.

## Optional Theme Locations ##

If you'd rather keep Geo Mashup templates and stylesheets in your theme folder or folders, that is also an
option.  Copy your files to these locations in the `wp-content` folder, where 

&lt;your-theme&gt;

 is the folder
of your active theme.

| **copy from `geo-mashup` folder** | **copy to** |
|:----------------------------------|:------------|
| `map-style.css` | `themes/<your-theme>/map-style.css` |
| `info-window.php` | `themes/<your-theme>/geo-mashup-info-window.php` |
| `full-post.php` | `themes/<your-theme>/geo-mashup-full-post.php` |

The files in your theme folder will be used by Geo Mashup 1.2 and later.

## About the Geo Mashup Custom Plugin ##

There is a separate plugin now that provides a place for the `custom.js` file,
and optionally any of the files above, as well as any other files you'd like to
preserve.

[Geo Mashup Custom plugin](http://wordpress-geo-mashup.googlecode.com/files/geo-mashup-custom-1.0.zip)

This plugin's directory, `geo-mashup-custom` will safely house your custom files. No new versions
will be released, so automatic upgrades will never be an option, and your files will not be deleted.

Geo Mashup Custom will try to copy the custom files listed above from the `geo-mashup` folder,
but file permissions on your server may not allow this. Check that all your files are safe in the
`geo-mashup-custom` folder. Once they are, Geo Mashup can be upgraded automatically
as often as you please without touching your files.