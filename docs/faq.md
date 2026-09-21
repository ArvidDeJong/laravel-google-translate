---
title: "FAQ"
nav_order: 9
description: "Short answers about darvis/laravel-google-translate: what it is, how it compares, versions, costs, failed calls, the API key and testing."
faq: true
---

# Frequently asked questions

{% for item in site.data.faq %}
## {{ item.q }}

{{ item.a | markdownify }}
{% endfor %}
