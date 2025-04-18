#!/bin/bash

# --- Configuration ---
DRY_RUN=false

# --- Helper Functions ---
# Helper function for cross-platform sed
safe_sed() {
    local pattern="$1"
    local replacement="$2"
    local file="$3"

    # Check if the file exists
    if [ ! -f "$file" ]; then
        if [ "$DRY_RUN" = true ]; then
            echo "[DRY RUN] Would skip update for non-existent file: $file"
        else
            echo "Skipping update for non-existent file: $file"
        fi
        return 1
    fi

    # Check if the pattern exists in the file using grep -F for literal matching
    if ! grep -Fq -- "$pattern" "$file"; then
        if [ "$DRY_RUN" = true ]; then
            echo "[DRY RUN] Pattern '$pattern' not found in $file. Would skip replacement."
        else
            echo "Pattern '$pattern' not found in $file. Skipping replacement."
        fi
        return 0 # Not an error, just nothing to do
    fi

    if [ "$DRY_RUN" = true ]; then
        echo "[DRY RUN] Would replace '$pattern' with '$replacement' in $file"
        return 0
    fi

    # Perform the replacement using sed -i with a backup file for compatibility
    # Escape for sed: basic escaping for / & \
    local escaped_pattern=$(echo "$pattern" | sed -e 's/[\\/&]/\\&/g')
    local escaped_replacement=$(echo "$replacement" | sed -e 's/[\\/&]/\\&/g')

    sed -i.bak "s/$escaped_pattern/$escaped_replacement/g" "$file"
    local sed_exit_code=$?

    if [ $sed_exit_code -eq 0 ]; then
        rm "${file}.bak" # Remove backup file on success
        echo "Updated '$pattern' to '$replacement' in $file"
        return 0
    else
        echo "Error updating $file with sed (exit code: $sed_exit_code)."
        # Attempt to restore from backup if it exists
        if [ -f "${file}.bak" ]; then
            mv "${file}.bak" "$file"
            echo "Restored $file from backup."
        fi
        return 1
    fi
}

# Helper function for renaming files
rename_file() {
    local old_path="$1"
    local new_path="$2"

    if [ ! -f "$old_path" ]; then
        if [ "$DRY_RUN" = true ]; then
            echo "[DRY RUN] Would skip rename: Source file not found: $old_path"
        else
            echo "Skipping rename: Source file not found: $old_path"
        fi
        return 1
    fi

    if [ -f "$new_path" ]; then
        if [ "$DRY_RUN" = true ]; then
            echo "[DRY RUN] Would skip rename: Target file already exists: $new_path"
        else
            echo "Skipping rename: Target file already exists: $new_path"
        fi
        return 1
    fi

    if [ "$DRY_RUN" = true ]; then
        echo "[DRY RUN] Would rename $old_path to $new_path"
        return 0 # Indicate success for dry run rename simulation
    fi

    mv "$old_path" "$new_path"
    local mv_exit_code=$?
    if [ $mv_exit_code -eq 0 ]; then
        echo "Renamed $old_path to $new_path"
        return 0
    else
        echo "Error renaming $old_path to $new_path (exit code: $mv_exit_code)."
        return 1
    fi
}

# --- Argument Parsing ---
for arg in "$@"
do
    case $arg in
        --dry-run)
        DRY_RUN=true
        shift # Remove --dry-run from processing
        ;;
        *)
        # Unknown option
        ;;
    esac
done

if [ "$DRY_RUN" = true ]; then
    echo "*** Running in DRY RUN mode. No files will be changed. ***"
fi

# --- Main Script ---

# Prompt for package name
read -p "Enter the package name (e.g., imagewize/sage-native-block): " PACKAGE_NAME
if [ -z "$PACKAGE_NAME" ]; then
    echo "Package name cannot be empty. Exiting."
    exit 1
fi
if ! echo "$PACKAGE_NAME" | grep -q '/'; then
    echo "Invalid package name format. Use 'vendor/package-name'. Exiting."
    exit 1
fi

# Extract vendor and package parts
VENDOR_NAME=$(echo $PACKAGE_NAME | awk -F'/' '{print $1}')
PACKAGE_BASE_NAME=$(echo $PACKAGE_NAME | awk -F'/' '{print $2}')

# Convert vendor to PascalCase (Capitalize first letter)
PASCAL_VENDOR_NAME="$(tr '[:lower:]' '[:upper:]' <<< ${VENDOR_NAME:0:1})${VENDOR_NAME:1}"

# Convert kebab-case package name to PascalCase
if command -v perl > /dev/null; then
    PASCAL_CASE_NAME=$(echo "$PACKAGE_BASE_NAME" | perl -pe 's/(^|-)./uc($&)/ge; s/-//g')
else
    echo "Warning: perl not found. Using sed for PascalCase conversion, which might be less reliable." >&2
    PASCAL_CASE_NAME=$(echo "$PACKAGE_BASE_NAME" | sed -e 's/-\([a-zA-Z0-9]\)/\U\1/g' -e 's/^[a-z]/\U&/')
    PASCAL_CASE_NAME=$(echo "$PASCAL_CASE_NAME" | sed 's/-//g')
fi

# --- Define old and new strings/paths --- 

# Namespaces
OLD_NAMESPACE_PHP="VendorName\\ExamplePackage" # Original namespace in PHP files (single backslash for PHP)
NEW_NAMESPACE_PHP="${PASCAL_VENDOR_NAME}\\$PASCAL_CASE_NAME" # New namespace for PHP files (single backslash for PHP)
# Literal string for grep -F and sed pattern (double backslash as it appears in the JSON file)
OLD_COMPOSER_PSR4_KEY='"VendorName\\ExamplePackage\\"'
# Literal string for sed replacement (double backslash as it should appear in the JSON file)
NEW_COMPOSER_PSR4_KEY="\"${PASCAL_VENDOR_NAME}\\\\${PASCAL_CASE_NAME}\\\\\""

# Service Provider
OLD_SERVICE_PROVIDER_FILE="src/Providers/ExampleServiceProvider.php"
NEW_SERVICE_PROVIDER_FILE="src/Providers/${PASCAL_CASE_NAME}ServiceProvider.php"
OLD_SERVICE_PROVIDER_CLASS="ExampleServiceProvider"
NEW_SERVICE_PROVIDER_CLASS="${PASCAL_CASE_NAME}ServiceProvider"
OLD_SERVICE_PROVIDER_FQN_PHP="VendorName\\ExamplePackage\\Providers\\ExampleServiceProvider" # Single backslashes for use in PHP/README
NEW_SERVICE_PROVIDER_FQN_PHP="${PASCAL_VENDOR_NAME}\\${PASCAL_CASE_NAME}\\Providers\\${PASCAL_CASE_NAME}ServiceProvider"
OLD_SERVICE_PROVIDER_FQN_JSON="VendorName\\\\ExamplePackage\\\\Providers\\\\ExampleServiceProvider" # Double backslashes for matching in JSON
NEW_SERVICE_PROVIDER_FQN_JSON="${PASCAL_VENDOR_NAME}\\\\${PASCAL_CASE_NAME}\\\\Providers\\\\${PASCAL_CASE_NAME}ServiceProvider"

# Facade
OLD_FACADE_FILE="src/Facades/Example.php"
NEW_FACADE_FILE="src/Facades/${PASCAL_CASE_NAME}.php"
OLD_FACADE_CLASS="Example"
NEW_FACADE_CLASS="$PASCAL_CASE_NAME"
OLD_FACADE_ACCESSOR="'Example'"
NEW_FACADE_ACCESSOR="'$PASCAL_CASE_NAME'"
OLD_FACADE_FQN_PHP="VendorName\\ExamplePackage\\Facades\\Example" # Single backslashes for use in PHP/README
NEW_FACADE_FQN_PHP="${PASCAL_VENDOR_NAME}\\${PASCAL_CASE_NAME}\\Facades\\${PASCAL_CASE_NAME}"
OLD_FACADE_FQN_JSON="VendorName\\\\ExamplePackage\\\\Facades\\\\Example" # Double backslashes for matching in JSON
NEW_FACADE_FQN_JSON="${PASCAL_VENDOR_NAME}\\\\${PASCAL_CASE_NAME}\\\\Facades\\\\${PASCAL_CASE_NAME}"

# Main Class
OLD_CLASS_FILE="src/Example.php"
NEW_CLASS_FILE="src/${PASCAL_CASE_NAME}.php"
OLD_CLASS_NAME="Example"
NEW_CLASS_NAME="$PASCAL_CASE_NAME"

# Command
OLD_COMMAND_FILE="src/Console/ExampleCommand.php"
NEW_COMMAND_FILE="src/Console/${PASCAL_CASE_NAME}Command.php"
OLD_COMMAND_CLASS="ExampleCommand"
NEW_COMMAND_CLASS="${PASCAL_CASE_NAME}Command"
OLD_COMMAND_SIGNATURE="'example'"
NEW_COMMAND_SIGNATURE="'${PACKAGE_BASE_NAME}'"
OLD_COMMAND_WPCLI="wp acorn example"
NEW_COMMAND_WPCLI="wp acorn ${PACKAGE_BASE_NAME}"

# Config
OLD_CONFIG_FILE="config/example.php"
NEW_CONFIG_FILE="config/${PACKAGE_BASE_NAME}.php"
OLD_CONFIG_KEY_IN_PROVIDER="'example'" # Used in Service Provider
NEW_CONFIG_KEY_IN_PROVIDER="'${PACKAGE_BASE_NAME}'"
OLD_CONFIG_KEY_IN_MAIN_CLASS="config('example." # Used in Main Class (matches start of config call)
NEW_CONFIG_KEY_IN_MAIN_CLASS="config('${PACKAGE_BASE_NAME}."
OLD_CONFIG_FILENAME="example.php"
NEW_CONFIG_FILENAME="${PACKAGE_BASE_NAME}.php"

# View
OLD_VIEW_FILE="resources/views/example.blade.php"
NEW_VIEW_FILE="resources/views/${PACKAGE_BASE_NAME}.blade.php"
OLD_VIEW_NAMESPACE="'Example'"
NEW_VIEW_NAMESPACE="'$PASCAL_CASE_NAME'"
OLD_VIEW_INCLUDE="@include('Example::example')"
NEW_VIEW_INCLUDE="@include('$PASCAL_CASE_NAME::$PACKAGE_BASE_NAME')"

# Other Files
README_FILE="README.md"
COMPOSER_FILE="composer.json"

# Specific strings in composer.json
OLD_COMPOSER_NAME='"name": "vendor-name/example-package"'
NEW_COMPOSER_NAME="\"name\": \"$PACKAGE_NAME\""
OLD_COMPOSER_DESC='"description": "An example package for Roots Acorn."'
NEW_COMPOSER_DESC="\"description\": \"$PASCAL_CASE_NAME package for Sage\""
OLD_COMPOSER_PROVIDER_ENTRY="\"${OLD_SERVICE_PROVIDER_FQN_JSON}\"" # Needs double escaping for JSON string quotes
NEW_COMPOSER_PROVIDER_ENTRY="\"${NEW_SERVICE_PROVIDER_FQN_JSON}\""
OLD_COMPOSER_ALIAS_KEY="\"Example\": "
NEW_COMPOSER_ALIAS_KEY="\"$PASCAL_CASE_NAME\": "
OLD_COMPOSER_ALIAS_VALUE="\"${OLD_FACADE_FQN_JSON}\"" # Needs double escaping for JSON string quotes
NEW_COMPOSER_ALIAS_VALUE="\"${NEW_FACADE_FQN_JSON}\""

echo "--- Starting Package Setup for $PACKAGE_NAME ---"

# 1. Update composer.json
echo "Updating composer.json..."
safe_sed "$OLD_COMPOSER_NAME" "$NEW_COMPOSER_NAME" "$COMPOSER_FILE"
safe_sed "$OLD_COMPOSER_DESC" "$NEW_COMPOSER_DESC" "$COMPOSER_FILE"
# Use the literal PSR-4 key strings with double backslashes
safe_sed "$OLD_COMPOSER_PSR4_KEY" "$NEW_COMPOSER_PSR4_KEY" "$COMPOSER_FILE"
safe_sed "$OLD_COMPOSER_PROVIDER_ENTRY" "$NEW_COMPOSER_PROVIDER_ENTRY" "$COMPOSER_FILE"
safe_sed "$OLD_COMPOSER_ALIAS_KEY" "$NEW_COMPOSER_ALIAS_KEY" "$COMPOSER_FILE"
safe_sed "$OLD_COMPOSER_ALIAS_VALUE" "$NEW_COMPOSER_ALIAS_VALUE" "$COMPOSER_FILE"
echo "----------------------------------------"

# 2. Update namespace globally in PHP files
echo "Updating namespaces in PHP files..."
find ./src -name "*.php" -type f | while read file; do
    # Skip files that might be renamed but not yet processed
    if [[ "$file" == *"ExampleServiceProvider.php"* ]] || [[ "$file" == *"Facades/Example.php"* ]] || [[ "$file" == *"src/Example.php"* ]] || [[ "$file" == *"Console/ExampleCommand.php"* ]]; then
        if [ ! -f "$file" ] && [ "$DRY_RUN" = true ]; then
             echo "[DRY RUN] Skipping namespace update for potential rename target: $file"
             continue
        elif [ ! -f "$file" ]; then
             continue # Skip if file doesn't exist (already renamed?)
        fi
    fi
    # Use OLD/NEW_NAMESPACE_PHP which have single backslashes for PHP code
    safe_sed "namespace $OLD_NAMESPACE_PHP" "namespace $NEW_NAMESPACE_PHP" "$file"
    safe_sed "use $OLD_NAMESPACE_PHP" "use $NEW_NAMESPACE_PHP" "$file"
done
echo "----------------------------------------"

# Function to handle content updates after potential rename
update_renamed_file_content() {
    local old_file="$1"
    local new_file="$2"
    shift 2 # Remove file paths from arguments
    local sed_commands=("$@") # Remaining arguments are sed commands

    if [ "$DRY_RUN" = true ]; then
        # In dry run, simulate based on whether rename *would* happen
        if [ -f "$old_file" ] && [ ! -f "$new_file" ]; then
            echo "[DRY RUN] Simulating content update for: $new_file (after rename of $old_file)"
            # Directly print the intended actions instead of calling safe_sed
            for (( i=0; i<${#sed_commands[@]}; i+=2 )); do
                local pattern="${sed_commands[i]}"
                local replacement="${sed_commands[i+1]}"
                # Check if pattern would be found in the *original* file (best guess for dry run)
                if grep -Fq -- "$pattern" "$old_file"; then
                    echo "[DRY RUN] Would replace '$pattern' with '$replacement' in $new_file"
                else
                    echo "[DRY RUN] Pattern '$pattern' likely not found in $old_file. Would skip replacement in $new_file."
                fi
            done
        elif [ ! -f "$old_file" ]; then
            echo "[DRY RUN] Skipping content update simulation for $new_file as source $old_file doesn't exist."
        elif [ -f "$new_file" ]; then
            echo "[DRY RUN] Skipping content update simulation for $new_file as it already exists (rename wouldn't happen). Content check skipped."
        fi
    else
        # In normal run, only update if the new file actually exists
        if [ -f "$new_file" ]; then
            for (( i=0; i<${#sed_commands[@]}; i+=2 )); do
                local pattern="${sed_commands[i]}"
                local replacement="${sed_commands[i+1]}"
                safe_sed "$pattern" "$replacement" "$new_file"
            done
        else
             echo "Skipping content update for $new_file as it does not exist (rename might have failed or skipped)."
        fi
    fi
}

# 3. Rename and Update Service Provider
echo "Processing Service Provider..."
rename_file "$OLD_SERVICE_PROVIDER_FILE" "$NEW_SERVICE_PROVIDER_FILE"
update_renamed_file_content "$OLD_SERVICE_PROVIDER_FILE" "$NEW_SERVICE_PROVIDER_FILE" \
    "class $OLD_SERVICE_PROVIDER_CLASS" "class $NEW_SERVICE_PROVIDER_CLASS" \
    "singleton($OLD_FACADE_ACCESSOR" "singleton($NEW_FACADE_ACCESSOR" \
    "$OLD_CONFIG_FILENAME" "$NEW_CONFIG_FILENAME" \
    "$OLD_CONFIG_KEY_IN_PROVIDER" "$NEW_CONFIG_KEY_IN_PROVIDER" \
    "$OLD_VIEW_NAMESPACE" "$NEW_VIEW_NAMESPACE" \
    "$OLD_COMMAND_CLASS::class" "$NEW_COMMAND_CLASS::class" \
    "make($OLD_FACADE_ACCESSOR)" "make($NEW_FACADE_ACCESSOR)" \
    "namespace $OLD_NAMESPACE_PHP\\Providers" "namespace $NEW_NAMESPACE_PHP\\Providers" \
    "use $OLD_NAMESPACE_PHP\\Console\\$OLD_COMMAND_CLASS" "use $NEW_NAMESPACE_PHP\\Console\\$NEW_COMMAND_CLASS" \
    "use $OLD_NAMESPACE_PHP\\$OLD_CLASS_NAME" "use $NEW_NAMESPACE_PHP\\$NEW_CLASS_NAME"
echo "----------------------------------------"

# 4. Rename and Update Facade
echo "Processing Facade..."
rename_file "$OLD_FACADE_FILE" "$NEW_FACADE_FILE"
update_renamed_file_content "$OLD_FACADE_FILE" "$NEW_FACADE_FILE" \
    "class $OLD_FACADE_CLASS extends Facade" "class $NEW_FACADE_CLASS extends Facade" \
    "return $OLD_FACADE_ACCESSOR;" "return $NEW_FACADE_ACCESSOR;" \
    "namespace $OLD_NAMESPACE_PHP\\Facades" "namespace $NEW_NAMESPACE_PHP\\Facades"
echo "----------------------------------------"

# 5. Rename and Update Main Class
echo "Processing Main Class..."
rename_file "$OLD_CLASS_FILE" "$NEW_CLASS_FILE"
update_renamed_file_content "$OLD_CLASS_FILE" "$NEW_CLASS_FILE" \
    "class $OLD_CLASS_NAME" "class $NEW_CLASS_NAME" \
    "$OLD_CONFIG_KEY_IN_MAIN_CLASS" "$NEW_CONFIG_KEY_IN_MAIN_CLASS" \
    "namespace $OLD_NAMESPACE_PHP" "namespace $NEW_NAMESPACE_PHP"
echo "----------------------------------------"

# 6. Rename and Update Console Command
echo "Processing Console Command..."
rename_file "$OLD_COMMAND_FILE" "$NEW_COMMAND_FILE"
update_renamed_file_content "$OLD_COMMAND_FILE" "$NEW_COMMAND_FILE" \
    "class $OLD_COMMAND_CLASS extends Command" "class $NEW_COMMAND_CLASS extends Command" \
    "protected \$signature = $OLD_COMMAND_SIGNATURE;" "protected \$signature = $NEW_COMMAND_SIGNATURE;" \
    "namespace $OLD_NAMESPACE_PHP\\Console" "namespace $NEW_NAMESPACE_PHP\\Console" \
    "use $OLD_NAMESPACE_PHP\\Facades\\$OLD_FACADE_CLASS" "use $NEW_NAMESPACE_PHP\\Facades\\$NEW_FACADE_CLASS"
echo "----------------------------------------"

# 7. Rename Config File
echo "Processing Config File..."
rename_file "$OLD_CONFIG_FILE" "$NEW_CONFIG_FILE"
echo "----------------------------------------"

# 8. Rename View File
echo "Processing View File..."
rename_file "$OLD_VIEW_FILE" "$NEW_VIEW_FILE"
echo "----------------------------------------"

# 9. Update README.md
echo "Updating README.md..."
if [ -f "$README_FILE" ]; then
    safe_sed "Acorn Example Package" "$PASCAL_CASE_NAME" "$README_FILE"
    safe_sed "vendor-name/example-package" "$PACKAGE_NAME" "$README_FILE"
    # Use the FQN PHP strings which have single backslashes suitable for README
    safe_sed "$OLD_SERVICE_PROVIDER_FQN_PHP" "$NEW_SERVICE_PROVIDER_FQN_PHP" "$README_FILE"
    safe_sed "$OLD_VIEW_INCLUDE" "$NEW_VIEW_INCLUDE" "$README_FILE"
    safe_sed "$OLD_COMMAND_WPCLI" "$NEW_COMMAND_WPCLI" "$README_FILE"
fi
echo "----------------------------------------"

echo "--- Package setup completed for: $PACKAGE_NAME ---"
if [ "$DRY_RUN" = false ]; then
    echo "Review the changes and run 'composer dump-autoload' if necessary."
else
    echo "*** Dry run finished. No files were changed. ***"
fi