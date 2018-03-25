---
title: Edit Recipe

cache_enable: false

twig_first: true
process:
  twig: true

form:
  name: recipebook-edit
  method: post

  fields:
    - name: name
      type: text
      id: name
      autofocus: true
      validate:
        required: true
    - name: yields
      type: text
      id: yields
    - name: notes
      type: textarea
      id: notes
      placeholder: Accepts markdown style entries
    - name: ingredients
      type: textarea
      id: ingredients
      rows: 10
      placeholder: Accepts a markdown style list
      validate:
          required: true
    - name: directions
      type: textarea
      id: directions
      rows: 10
      placeholder: Accepts markdown style entries
      validate:
          required: true
    - name: tags
      placeholder: Enter Tags (comma seperated)
      type: text
      id: tags
---

{{ dump(recipe) }}

<script>

  var r = {{ recipe|json_encode|raw }};
  console.log(r);

  $("#name").val(r.name);
  $("#notes").val(r.notes);
  $("#yields").val(r.yields);
  $("#directions").val(r.directions);
  
  // switch to for/in loop
  var ingr_list = "";
  for (ingr in r.ingredients) { 
    ingr_list += "- " + r.ingredients[ingr] + "\n";
  }
  $("#ingredients").val(ingr_list);

  var tag_list = "";
  for (tag in r.tags) {
    tag_list += r.tags[tag] + ", ";
  }
  tag_list = tag_list.substring(0, tag_list.length - 2);
  $("#tags").val(tag_list);
</script>