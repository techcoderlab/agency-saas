<script setup>
import { computed } from 'vue'
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue'

const props = defineProps({
  schema: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update:modelValue'])

const valueFor = (field) =>
  computed(() => props.modelValue[field.name] ?? (field.type === 'checkbox-group' || field.multiple ? [] : ''))

const updateField = (name, value) => {
  emit('update:modelValue', {
    ...props.modelValue,
    [name]: value,
  })
}

const handleCheckboxGroup = (field, optionValue, checked) => {
  const current = Array.isArray(props.modelValue[field.name]) ? [...props.modelValue[field.name]] : []
  if (checked) {
    if (!current.includes(optionValue)) current.push(optionValue)
  } else {
    const idx = current.indexOf(optionValue)
    if (idx !== -1) current.splice(idx, 1)
  }
  updateField(field.name, current)
}

const fileSummary = (files) => {
  if (!files || !files.length) return ''
  return Array.from(files).map((f) => f.name).join(', ')
}
</script>

<template>
  <form class="space-y-4">
    <div
      v-for="(field, index) in schema"
      :key="field.name || index"
      class="space-y-1"
    >
      <div class="flex items-center justify-between">
        <label class="block text-sm font-medium text-slate-200">
          {{ field.label || field.name }}
          <span
            v-if="field.required"
            class="text-red-500"
          >
            *
          </span>
        </label>
        <span
          v-if="field.hint"
          class="text-xs text-slate-400"
        >
          {{ field.hint }}
        </span>
      </div>

      <!-- Text / Email / Number -->
      <input
        v-if="['text', 'email', 'number'].includes(field.type)"
        :type="field.type"
        :name="field.name"
        class="w-full rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
        :placeholder="field.placeholder"
        :required="field.required"
        :value="valueFor(field).value"
        @input="updateField(field.name, $event.target.value)"
      />

      <!-- Textarea -->
      <textarea
        v-else-if="field.type === 'textarea'"
        :name="field.name"
        class="w-full rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
        :placeholder="field.placeholder"
        :required="field.required"
        :value="valueFor(field).value"
        @input="updateField(field.name, $event.target.value)"
      />

      <!-- Select (Headless UI Listbox single) -->
      <Listbox
        v-else-if="field.type === 'select' && !field.multiple"
        :model-value="valueFor(field).value || ''"
        @update:model-value="(val) => updateField(field.name, val)"
      >
        <div class="relative mt-1">
          <ListboxButton
            class="relative w-full cursor-default rounded-lg bg-slate-900/60 py-2 pl-3 pr-10 text-left text-sm text-slate-100 shadow-md border border-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
          >
            <span class="block truncate">
              {{
                (field.options || []).find((o) => o.value === valueFor(field).value)?.label ||
                  field.placeholder ||
                  'Select...'
              }}
            </span>
          </ListboxButton>
          <ListboxOptions
            class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-slate-900 py-1 text-sm shadow-lg ring-1 ring-black/5 focus:outline-none z-20"
          >
            <ListboxOption
              v-for="option in field.options || []"
              :key="option.value"
              v-slot="{ active, selected }"
              :value="option.value"
              as="template"
            >
              <li
                class="relative cursor-default select-none py-2 pl-3 pr-9"
                :class="[
                  active ? 'bg-violet-600 ' : 'text-slate-100',
                  selected ? 'font-medium' : 'font-normal',
                ]"
              >
                <span class="block truncate">
                  {{ option.label }}
                </span>
              </li>
            </ListboxOption>
          </ListboxOptions>
        </div>
      </Listbox>

      <!-- Native multi-select -->
      <select
        v-else-if="field.type === 'select' && field.multiple"
        :name="field.name"
        multiple
        class="w-full rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
        :required="field.required"
        :value="valueFor(field).value"
        @change="
          updateField(
            field.name,
            Array.from($event.target.selectedOptions || []).map((o) => o.value),
          )
        "
      >
        <option
          v-for="option in field.options || []"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>

      <!-- Radio group -->
      <div
        v-else-if="field.type === 'radio-group'"
        class="flex flex-wrap gap-3"
      >
        <label
          v-for="option in field.options || []"
          :key="option.value"
          class="inline-flex items-center gap-2 rounded-full border border-slate-700 bg-slate-900/60 px-3 py-1 text-xs text-slate-100 cursor-pointer hover:border-violet-500"
        >
          <input
            type="radio"
            :name="field.name"
            class="text-violet-500 focus:ring-violet-500"
            :value="option.value"
            :checked="valueFor(field).value === option.value"
            @change="updateField(field.name, option.value)"
          />
          <span>{{ option.label }}</span>
        </label>
      </div>

      <!-- Checkbox group -->
      <div
        v-else-if="field.type === 'checkbox-group'"
        class="flex flex-col gap-2"
      >
        <label
          v-for="option in field.options || []"
          :key="option.value"
          class="inline-flex items-center gap-2 text-sm text-slate-100 cursor-pointer"
        >
          <input
            type="checkbox"
            class="rounded border-slate-600 bg-slate-900 text-violet-500 focus:ring-violet-500"
            :value="option.value"
            :checked="(valueFor(field).value || []).includes(option.value)"
            @change="handleCheckboxGroup(field, option.value, $event.target.checked)"
          />
          <span>{{ option.label }}</span>
        </label>
      </div>

      <!-- File upload -->
      <div
        v-else-if="field.type === 'file'"
        class="space-y-2"
      >
        <input
          type="file"
          :name="field.name"
          :multiple="field.multiple"
          class="block w-full text-sm text-slate-100 file:mr-4 file:rounded-md file:border-0 file:bg-violet-600 file:px-4 file:py-2 file:text-sm file:font-semibold file: hover:file:bg-violet-500"
          @change="
            updateField(
              field.name,
              Array.from($event.target.files || []).map((f) => ({
                name: f.name,
                size: f.size,
                type: f.type,
              })),
            )
          "
        />
        <p class="text-xs text-slate-400">
          {{ fileSummary(modelValue[field.name]) }}
        </p>
      </div>

      <!-- Color picker -->
      <input
        v-else-if="field.type === 'color'"
        type="color"
        :name="field.name"
        class="h-9 w-16 rounded border border-slate-700 bg-transparent"
        :value="valueFor(field).value || '#6366f1'"
        @input="updateField(field.name, $event.target.value)"
      />

      <!-- Range slider -->
      <div
        v-else-if="field.type === 'range'"
        class="space-y-1"
      >
        <input
          type="range"
          :name="field.name"
          class="w-full accent-violet-500"
          :min="field.min ?? 0"
          :max="field.max ?? 100"
          :step="field.step ?? 1"
          :value="valueFor(field).value || field.min || 0"
          @input="updateField(field.name, Number($event.target.value))"
        />
        <div class="text-xs text-slate-400">
          {{ valueFor(field).value || field.min || 0 }}
        </div>
      </div>

      <!-- Progress (display only) -->
      <div
        v-else-if="field.type === 'progress'"
        class="space-y-1"
      >
        <div class="h-2 w-full rounded-full bg-slate-800 overflow-hidden">
          <div
            class="h-full bg-violet-500"
            :style="{ width: `${field.value ?? 0}%` }"
          />
        </div>
        <div class="text-xs text-slate-400">
          {{ field.value ?? 0 }}%
        </div>
      </div>

      <!-- Fallback text -->
      <input
        v-else
        :name="field.name"
        type="text"
        class="w-full rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
        :placeholder="field.placeholder"
        :required="field.required"
        :value="valueFor(field).value"
        @input="updateField(field.name, $event.target.value)"
      />

      <p
        v-if="errors[field.name]"
        class="text-xs text-red-500"
      >
        {{ errors[field.name] }}
      </p>
    </div>
  </form>
</template>

