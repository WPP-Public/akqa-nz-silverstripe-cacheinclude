#Heyday Cache Include

HTML Caching can be added to your SilverStripe project by replacing <% include X %> calls with $CacheInclude(X) calls.

##License


##Installation

To install drop the `heyday-cacheinclude` directory into your SilverStripe root and run `/dev/build?flush=1`.

##How to use

###Templates

    $CacheInclude(TemplateName)

###Partials


    $CacheIncludePartial(
        PartialName,
        <ul class=\"nav\">
        {# control Menu{|1|} #}
            <li>{{Title}}</li>
        {# end_control #}
        </ul>
    )

In order to use partials, SilverStripes templating syntax has to be changed within the partial. The following characters or character combinations will cause errors in your partial:

* <%
* %>
* )
* ,
* $
* "

The following are the replacements to use.

<table>
  <tr>
    <th>Original</th><th>Replacement</th>
  </tr>
  <tr>
    <td>&lt;%</td><td>{#</td>
  </tr>
  <tr>
    <td>%&gt;</td><td>#}</td>
  </tr>
  <tr>
    <td>(</td><td>{|</td>
  </tr>
  <tr>
    <td>)</td><td>|}</td>
  </tr>
  <tr>
    <td>$</td><td>{{</td>
  </tr>
  <tr>
    <td>,</td><td>{%c%}</td>
  </tr>
  <tr>
    <td>"</td><td>\"</td>
  </tr>
</table>

##Configuration



##Clearing Cache



##Unit Testing

If you have `phpunit` installed you can run `heyday-cacheinclude`'s unit tests to see if everything is functioning correctly.

###Running the unit tests

From the command line:
    
    ./sake dev/tests/module/heyday-cacheinclude


From your browser:

    http://localhost/dev/tests/module/heyday-cacheinclude

##Contributing

###Code guidelines

This project follows the standards defined in:

* [PSR-1](https://github.com/pmjones/fig-standards/blob/psr-1-style-guide/proposed/PSR-1-basic.md)
* [PSR-2](https://github.com/pmjones/fig-standards/blob/psr-1-style-guide/proposed/PSR-2-advanced.md)
