---
title: New Recipe

form:
  name: recipebook-new
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

# Hello, World