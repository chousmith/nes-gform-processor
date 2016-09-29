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
8. Fill out the information there, and map fields for Lead Enrichment.
    * Run Enrichment : should pass value of 1 for Now or 2 for Later or 0 for Never
    * Email : at a minimum, you should provide an Email address to pass through to get Enriched
9. Then you Map additional fields to store the data returned by TowerData enrichment:
    * Date Enriched
    * Time Enriched
    * marital_status
    * net_worth
    * occupation
    * education
    * home_market_value
    * gender
    * length of residence
    * household_income
    * age
    * home_owner_status
    * presence_of_children
10. Click `Update Settings`, and then wait for form submissions to come through. If your `Run Enrichment` setting was set to "Now", they will be run through Enrichment. Otherwise, they will be ignored.

Your system will be set up to pipe form inputs through TowerData for Enrichment

Note: at this time, there is no way to go through and manually trigger Enrichment processing on submissions input for "Later" Enrichment. That might come some time, but also might wait until WordPress 4.7 is released, or whenever WordPress core contributors figure out this issue: https://core.trac.wordpress.org/ticket/16031
