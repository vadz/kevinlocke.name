---
layout: post
date: 2018-09-05 18:31:38-06:00
title: Azure Kudu Error 403 Without Referer
description: >-
    The Azure App Service Advanced Tools and site extension configuration
    interface (Kudu) gives Error 403 when the cross-origin Referer header is
    not sent.  This post discusses the details.
---

[Azure App Service](https://azure.microsoft.com/en-us/services/app-service/)
provides a management interface reachable through "Advanced Tools" in the
[Azure Portal](https://portal.azure.com) for controlling App Service features.
(This interface is part of the [Kudu](https://github.com/projectkudu/kudu)
project.)  Today I discovered that if your browser does not send the [HTTP
`Referer` header](https://tools.ietf.org/html/rfc7231#section-5.5.2) in
cross-origin requests, you will get Error 403 with the following content:

<!--more-->

> ## Error 403 - This web app is stopped.
> 
> The web app you have attempted to reach is currently stopped and does not
> accept any requests. Please try to reload the page or visit it again soon.
>
> If you are the web app administrator, please find the common 403 error
> scenarios and resolution
> [here](http://blogs.msdn.com/b/waws/archive/2016/01/05/azure-web-apps-error-403-this-web-app-is-stopped.aspx).
> For further troubleshooting tools and recommendations, please visit [Azure
> Portal](https://portal.azure.com/).

Although this error may occur due to the site being stopped, as the message
and linked blog post suggest, in my case the cause is the `Referer` header not
being sent.  I had configured Firefox with
[`network.http.referer.XOriginPolicy`](https://wiki.mozilla.org/Security/Referrer)
set to a non-zero value for privacy reasons.  Setting it to 0 resolved the
error.
