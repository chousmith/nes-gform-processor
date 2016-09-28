# NES GForm Processor
Ninthlink Enrichment System "nes" routes submissions in a particular Gravity Form for Lead Enrichment through TowerData. Some assembly may be required.

## Installation

This plugin is an add-on for Gravity Forms, so before installing this plugin, make sure you have Gravity Forms installed and activated on the site.

After that, follow these steps:

1. Download this WordPress plugin, and upload to `/wp-content/plugins/nes-gform-processor/`
2. Activate the plugin
3. Go to the plugin Gravity Forms Settings at `/wp-admin/admin.php?page=gf_settings&subview=nes-gform-processor` and enter the following:
    * Tower Data API Key
4. Click the `Update Settings` button to save those settings.
5. Go to the particular Gravity Forms form that you want to process
6. In the Settings for that particular form, click on "NES"
7. Click the `Add New` or `create one!` link to create a new Feed.
8. Fill out the information there, and map fields for Lead Enrichment. At a minimum, you should provide an Email address.

And then?
