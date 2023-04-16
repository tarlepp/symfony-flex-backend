#!/bin/bash

printf "%-15s %10s %14s\n" "Extension" "File count" "Line count"

echo -e "-----------------------------------------"

find ./src -type f -iname '*.*' | sed -n 's/.*\.//p' | tr '[:upper:]' '[:lower:]' | sort | uniq -c | while read count extension; do
    line_count=$(find ./src -name "*.$extension" -type f -print0 | xargs -0 cat | wc -l)
    printf "%-15s %10d %14d\n" ".$extension" "$count" "$line_count"
done

echo -e "-----------------------------------------"

total_files=$(find ./src -type f | wc -l)
total_lines=$(find ./src -type f -exec cat {} + | wc -l)

printf "%-15s %10d %14d\n" "Total:" "$total_files" "$total_lines"
