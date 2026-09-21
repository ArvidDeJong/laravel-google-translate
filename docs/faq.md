---
title: FAQ
nav_order: 8
description: "Short answers about darvis/laravel-google-translate: what it translates, the table layout, failed translations, costs, HTML and testing."
faq: true
---

# Frequently asked questions

{% for item in site.data.faq %}
## {{ item.q }}

{{ item.a | markdownify }}
{% endfor %}
