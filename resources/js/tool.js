Nova.booting((Vue, router, store) => {
  router.addRoutes([
    {
      name: 'taggable',
      path: '/taggable',
      component: require('./components/Tool'),
    },
  ])
})
