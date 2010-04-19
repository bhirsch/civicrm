{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<fieldset><legend>{ts}Contact Search{/ts}</legend>
    <table class="form-layout">
        <tr>
            <td>{$form.includeWildCardInName.label}</td>
            <td>{$form.includeWildCardInName.html}<br />
                <span class="description">{ts}If enabled, wildcards are automatically added when users search for contacts by Name. EXAMPLE: Searching for 'ada' will return any contact whose name includes those letters - e.g. 'Adams, Janet', 'Nadal, Jorge', etc. Disabling this feature will speed up search significantly for larger databases, but users must use MySQL wildcard characters for partial name searches (e.g. '%' or '_').{/ts}</span></td>
        </tr>
        <tr>
            <td>{$form.includeEmailInName.label}</td>
            <td>{$form.includeEmailInName.html}<br />
                <span class="description">{ts}If enabled, email addresses are automatically included when users search by Name. Disabling this feature will speed up search significantly for larger databases, but users will need to use the Email search fields (from Advanced Search, Search Builder, or Profiles) to find contacts by email address.{/ts}</span></td>
        </tr>
        <tr>
            <td>{$form.includeNickNameInName.label}</td>
            <td>{$form.includeNickNameInName.html}<br />
                <span class="description">{ts}If enabled, nicknames are automatically included when users search by Name.{/ts}</span></td>
        </tr>
        <tr>
            <td>{$form.includeAlphabeticalPager.label}</td>
            <td>{$form.includeAlphabeticalPager.html}<br />
                <span class="description">{ts}If disabled, the alphabetical pager will not be displayed on the search screens. This will improve response time on search results on large datasets.{/ts}</span></td>
        </tr>
        <tr>
            <td>{$form.includeOrderByClause.label}</td>
            <td>{$form.includeOrderByClause.html}<br />
                <span class="description">{ts}If disabled, the search results will not be ordered. This will improve response time on search results on large datasets significantly.{/ts}</span></td>
        </tr>
        <tr>
            <td>{$form.defaultSearchProfileID.label}</td>
            <td>{$form.defaultSearchProfileID.html}<br />
                <span class="description">{ts}If set, this will be the default profile used for contact search. This is experimental functionality.{/ts}</span></td>
        </tr>
        <tr>
            <td>{$form.smartGroupCacheTimeout.label}</td>
            <td>{$form.smartGroupCacheTimeout.html}<br />
                <span class="description">{ts}The number of minutes to cache smart group contacts. A value of '0' means the cache is emptied immediately when any contact is edited or a new one is added. If your contact data changes frequently, you may want to try setting this to a value of 5 minutes or so.{/ts}</span></td>
        </tr>
        <tr>
            <td>{$form.autocompleteContactSearch.label}</td>
            <td>{$form.autocompleteContactSearch.html}<br/>
            <span class="description">{ts}Selected fields will be displayed in autocomplete dropdown lists. Contact Name is always included.{/ts}</span></td>
        </tr>
        <tr>
            <td></td>
            <td>{$form.buttons.html}</td>
        </tr>
    </table>
</fieldset>
