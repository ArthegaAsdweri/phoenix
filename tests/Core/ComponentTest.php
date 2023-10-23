<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\Component
 */
class ComponentTest extends TestCase
{

    //---- TESTS

    /**
     * @covers ::renderComponent
     * @covers ::setVScript
     * @covers ::getVScript
     * @covers ::setVStyle
     * @covers ::getVStyle
     * @covers ::setName
     * @covers ::getName
     */
    public function testRenderComponent_ValidComponent_ReturnsContent(): void
    {
        $name = 'page-test';
        $component = new Component('Pages/Test/Components/page-test.vue');
        $component->setName($name);
        $component->renderComponent();
        $script = $component->getVScript();
        $this->assertEquals(
            'Vue.component("' . $name . '", {
    methods: {
        run() {
            return "run";
        }
    }
, template:`
    <div>
        test-component
        <template>
            preg_match_validation
        </template>
    </div>
`});',
            trim($script)
        );
        $style = $component->getVStyle();
        $this->assertEquals(
            '* {
    border: 1px solid red;
}',
            $style
        );
    }

    /**
     * @covers ::renderComponent
     * @covers ::setIsMixin
     * @covers ::isMixin
     */
    public function testRenderComponent_WithMixin_ReturnsContent(): void
    {
        $name = 'mixin-test';
        $component = new Component('Pages/Test/Components/mixin-test.vue');
        $component->setName($name);
        $component->setIsMixin(true);
        $component->renderComponent();
        $script = $component->getVScript();
        $this->assertEquals(
            'Vue.component("' . $name . '", {
    methods: {
        run() {
            return "run";
        }
    }
}',
            trim($script)
        );
        $style = $component->getVStyle();
        $this->assertEquals(
            '* {
    border: 3px solid red;
}',
            $style
        );
    }


    /**
     * @covers ::renderComponent
     * @covers ::setIsChild
     * @covers ::isChild
     */
    public function testRenderComponent_ChildComponent_ReturnsContent(): void
    {
        $name = 'child-test';
        $component = new Component('Pages/Test/Components/child-test.vue');
        $component->setName($name);
        $component->setIsChild(true);
        $component->renderComponent();
        $script = $component->getVScript();
        $this->assertEquals(
            'var childTest = {
    name: "child-test",
    methods: {
        run() {
            return "run";
        }
    }
, template:`
    <div>
        child-mixin
    </div>
`};',
            trim($script)
        );
        $style = $component->getVStyle();
        $this->assertEquals(
            '* {
    border: 5px solid red;
}',
            $style
        );
    }

    /**
     * @covers ::getMixinType
     * @covers ::setMixinType
     */
    public function testRemainingGettersAndSetters_Works(): void
    {
        $component = new Component('Pages/Test/Components/child-test.vue');
        $component->setMixinType('GLOBAL');
        $this->assertEquals('GLOBAL', $component->getMixinType());
    }
}