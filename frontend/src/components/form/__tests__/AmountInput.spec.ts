import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AmountInput from "../AmountInput.vue";

describe("AmountInput", () => {
  it("should render correctly", () => {
    const wrapper = mount(AmountInput, {
      props: {
        modelValue: 5000, // 50.00 euros en cents
      },
    });

    expect(wrapper.find("label").text()).toBe("Montant (€)");
    expect(wrapper.find("input").exists()).toBe(true);
  });

  it("should convert cents to euros for display", () => {
    const wrapper = mount(AmountInput, {
      props: {
        modelValue: 12345, // 123.45 euros en cents
      },
    });

    const input = wrapper.find("input").element as HTMLInputElement;
    expect(Number(input.value)).toBe(123.45);
  });

  it("should emit euros converted to cents when input changes", async () => {
    const wrapper = mount(AmountInput, {
      props: {
        modelValue: 0,
      },
    });

    const input = wrapper.find("input");
    await input.setValue("50.75");

    expect(wrapper.emitted("update:modelValue")).toBeTruthy();
    expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([5075]); // 50.75 euros = 5075 cents
  });

  it("should display error message when error prop is provided", () => {
    const wrapper = mount(AmountInput, {
      props: {
        modelValue: 0,
        error: "Le montant est requis",
      },
    });

    expect(wrapper.find(".text-red-600").text()).toBe("Le montant est requis");
  });

  it("should add error border class when error prop is provided", () => {
    const wrapper = mount(AmountInput, {
      props: {
        modelValue: 0,
        error: "Le montant est requis",
      },
    });

    const input = wrapper.find("input");
    expect(input.classes()).toContain("border-red-500");
  });

  it("should not display error message when no error prop", () => {
    const wrapper = mount(AmountInput, {
      props: {
        modelValue: 0,
      },
    });

    expect(wrapper.find(".text-red-600").exists()).toBe(false);
  });

  it("should handle zero value correctly", () => {
    const wrapper = mount(AmountInput, {
      props: {
        modelValue: 0,
      },
    });

    const input = wrapper.find("input").element as HTMLInputElement;
    expect(Number(input.value)).toBe(0);
  });

  it("should handle string value correctly", () => {
    const wrapper = mount(AmountInput, {
      props: {
        modelValue: "10000", // string "10000" = 100.00 euros
      },
    });

    const input = wrapper.find("input").element as HTMLInputElement;
    expect(Number(input.value)).toBe(100);
  });

  it("should round to nearest cent when converting euros to cents", async () => {
    const wrapper = mount(AmountInput, {
      props: {
        modelValue: 0,
      },
    });

    const input = wrapper.find("input");
    await input.setValue("50.556"); // Should round to 5056 cents (50.56€)

    expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([5056]);
  });
});
