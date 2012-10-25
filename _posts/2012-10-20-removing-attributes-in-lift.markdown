---
layout: post
date: 2012-10-20 15:05:22-06:00
updated: 2012-10-24 20:59:54-06:00
title: Removing Attributes with Lift CSS Selector Transforms
description: "A brief discussion of how to, and how not to, remove attributes \
from HTML elements using Lift's CSS Selector Transforms."
tags: [ scala, lift ]
---
Recent versions of [Lift](http://liftweb.net/) (2.2-M1 and later) provide a
concise way of expressing XML transformations using a CSS-like syntax called
[CSS Selector Transforms](http://simply.liftweb.net/index-7.10.html).  The
pleasant conciseness comes with a number of unexpected/undocumented behaviors
and corner-cases.  One which recently caught me by surprise is the handling
of attributes on XML elements.  This post is a brief discussion of the
behavior and how to work around it to remove attributes from elements.

<!--more-->

## Attribute Merging

Although I can not find any reference to attribute merging in the
documentation, it has been
[discussed](https://groups.google.com/d/topic/liftweb/Vi4LkGV_IPc/discussion)
on the mailing list.  The basic idea is that when an element is transformed,
any attributes from the original element are copied to the replacement
element, except where the replacement element contains an attribute with the
same name (except when that attribute is "class", in which case they are
merged).  This simplifies the common case, where attributes should be
retained, while making the uncommon case significantly more complex.

## Example Transformations

For all of the following examples, suppose we have the following HTML stored
in a variable named `html`:

{% highlight html %}
<p id="notice1" class="admonition important">Be careful with CSS Selector Transforms!</p>
{% endhighlight %}

First, as an example of attribute merging, suppose we wanted to replace the
element with another using the following code:

{% highlight scala %}
("#notice1" #> <p id="notice2" class="alert">Breaking News:  CSS Selectors!</p>)(html)
{% endhighlight %}

This would produce:

{% highlight html %}
<p id="notice2" class="alert admonition important">Breaking News:  CSS Selectors!</p>
{% endhighlight %}

Notice that the id attribute has been replaced while the class attribute has
been merged.  With this in mind, it should be obvious why the following code
does not work:

{% highlight scala %}
// WARNING:  Does nothing!
("#notice1" #> { n =>
  val e = n.asInstanceOf[Elem];
  e.copy(attributes = e.attributes.remove("class"))
})(html)
{% endhighlight %}

Although the element returned by the transformation function doesn't have a
class attribute, attribute merging will add the class attribute from the
original element causing the above transformation to have no effect (except
possibly changing the attribute order).

The correct way to modify attributes is by matching against the attribute (a
Lift addition to the CSS syntax) as follows:

{% highlight scala %}
("#notice1 [id]" #> (None: Option[String]))(html)
{% endhighlight %}

This would (as expected) result in:

{% highlight html %}
<p class="admonition important">Breaking News:  CSS Selectors!</p>
{% endhighlight %}

Although, interestingly, replacing `None` with `Nil` will result in an empty
id attribute.  I haven't fully investigated this behavior, although part of
the explanation is that `None` is implicitly converted to
`net.liftweb.util.IterableConst` while `Nil` is implicitly converted to
`scala.xml.NodeSeq`.

In 2.4-M4, there is even an [addition to the
syntax](https://github.com/lift/framework/issues/1030) to allow removing a
space-separated word from an attribute:

{% highlight scala %}
("#notice1 [class!]" #> "important")(html)
{% endhighlight %}

Which would result in:

{% highlight html %}
<p id="notice1" class="admonition">Breaking News:  CSS Selectors!</p>
{% endhighlight %}

Neat, huh?

## Another Gotcha

Not so fast!  There's an [annoying
bug](https://github.com/lift/framework/issues/1312) which prevents this from
working when combined with other CSS Selector Transforms either as child
transforms (as appears in the bug report) or when combined.  So if we add an
identity transformation function to the previous transformation as follows:

{% highlight scala %}
// WARNING:  Does nothing!
("#notice1" #> { n => n } & "#notice1 [class!]" #> "important")(html)
{% endhighlight %}

The output is the same as the input (again, ignoring any attribute ordering).
After a bit of digging, I found that if the attribute-modifying transforms are
chained rather than combined they behave as expected.  So the previous
(non-functional) transformation can be changed to:

{% highlight scala %}
("#notice1" #> { n => n } andThen "#notice1 [class!]" #> "important")(html)
{% endhighlight %}

Which does behave as expected.

## Disabling Attribute Merging

In Lift 2.5-M1 and later there is an addition to the CSS Selector Transform
syntax which [disables attribute
merging](https://groups.google.com/d/msg/liftweb/sCNCVcjOZwo/kH9pNurlRKsJ).
Appending `"!!"` to the outermost selector will disable attribute merging,
which allows our original example (or any other nested selectors) to modify
attributes at will:

{% highlight scala %}
// WARNING:  Won't compile in 2.5-M1 or later
("#notice1 !!" #> { n => n } & "#notice1 [class!]" #> "important")(html)
{% endhighlight %}

Whoops!  That doesn't work, for 2 reasons.  First, `n` needs a declared type.
Second, the design of the new CSS Type Classes in 2.5 mean that the `html`
parameter is interpreted as the implicit `ComputeTransformRules` parameter of
the `#>` method of `ToCssBindPromoter` rather than the parameter of the
`apply` method of `CssSel`.  These problems can be fixed as follows:

{% highlight scala %}
// Works in 2.5-M1 and later
("#notice1 !!" #> { n: NodeSeq => n } & "#notice1 [class!]" #> "important").apply(html)
{% endhighlight %}

However, this syntax does not appear to affect function transforms, so the
following code still does not work in 2.5-M1:

{% highlight scala %}
// WARNING:  Still doesn't work in 2.5-M1
("#notice1 !!" #> { n: NodeSeq =>
  val e = n.asInstanceOf[Elem];
  e.copy(attributes = e.attributes.remove("class"))
}).apply(html)
{% endhighlight %}

## Article Changes

### 2012-10-24

* Added "Disabling Attribute Merging" section with information about Lift
  2.5-M1 and later.
