#!/usr/bin/env bash

set -euo pipefail

event_name="${EVENT_NAME:-}"
ref_name="${GITHUB_REF_NAME:-}"
test_tag="${RELEASE_TEST_TAG:-}"
release_mode="${RELEASE_MODE:-prerelease}"
changelog_path="${CHANGELOG_PATH:-CHANGELOG.md}"
release_notes_path="${RELEASE_NOTES_PATH:-release-notes.md}"

if [ ! -f "$changelog_path" ]; then
  echo "CHANGELOG file not found: $changelog_path" >&2
  exit 1
fi

write_output() {
  local key="$1"
  local value="$2"

  if [ -n "${GITHUB_OUTPUT:-}" ]; then
    printf '%s=%s\n' "$key" "$value" >> "$GITHUB_OUTPUT"
  fi
}

extract_unreleased_notes() {
  awk '
    $0 == "## [Unreleased]" {found=1; next}
    found && /^## \[/ {exit}
    found {print}
  ' "$changelog_path" > "$release_notes_path"

  if ! grep -q '[^[:space:]]' "$release_notes_path"; then
    echo "CHANGELOG.md must include release notes under ## [Unreleased] for manual test releases" >&2
    exit 1
  fi
}

extract_version_notes() {
  local version="$1"

  if ! grep -Eq "^## \[$version\] - [0-9]{4}-[0-9]{2}-[0-9]{2}$" "$changelog_path"; then
    echo "CHANGELOG.md must include heading: ## [$version] - YYYY-MM-DD" >&2
    exit 1
  fi

  awk -v version="$version" '
    $0 ~ "^## \\["version"\\]" {found=1; next}
    found && /^## \[/ {exit}
    found {print}
  ' "$changelog_path" > "$release_notes_path"

  if ! grep -q '[^[:space:]]' "$release_notes_path"; then
    echo "Changelog section for $version has no release notes" >&2
    exit 1
  fi
}

case "$event_name" in
  push)
    if ! [[ "$ref_name" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
      echo "Tag '$ref_name' must match vMAJOR.MINOR.PATCH" >&2
      exit 1
    fi

    release_tag="$ref_name"
    release_name="$ref_name"
    prerelease="false"

    extract_version_notes "${ref_name#v}"
    ;;
  workflow_dispatch)
    if [ "$release_mode" != "prerelease" ]; then
      echo "Manual release mode must be 'prerelease'" >&2
      exit 1
    fi

    if ! [[ "$test_tag" =~ ^test-[0-9A-Za-z][0-9A-Za-z.-]*$ ]]; then
      echo "Manual test tag '$test_tag' must match test-<identifier>" >&2
      exit 1
    fi

    release_tag="$test_tag"
    release_name="$test_tag"
    prerelease="true"

    extract_unreleased_notes
    ;;
  *)
    echo "Unsupported event '$event_name'. Expected push or workflow_dispatch." >&2
    exit 1
    ;;
esac

write_output "release_tag" "$release_tag"
write_output "release_name" "$release_name"
write_output "prerelease" "$prerelease"
